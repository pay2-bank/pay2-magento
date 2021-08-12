<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_NotificationsController extends Mage_Core_Controller_Front_Action
{

    public function callbackAction() {

        if (!$this->getRequest()->isPost()) {
            $this->getResponse()->setHttpResponseCode(405);
            return;
        }

        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json', true);

        $helper = Mage::helper('pix');

        $helper->log(print_r($this->getRequest()->getRawBody(), 1), 'pix_body_notification.log');
        $data = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());

        if ($data !== null) {

            try {
                
                $orderId = $data['external_identifier'];

                /** @var $order Mage_Sales_Model_Order */
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

                if ($order->getId()) {

                    $payment = $order->getPayment();
                    $transactionId = $payment->getData()['additional_information']['transactionId'];

                    $transaction = $helper->getClient()->find($transactionId);
                    $transaction = $transaction['callback_raw_data'];
                    
                    switch ($transaction['financialStatement']['status']) {
                        case 'APPROVED':
                            $payment->setCcStatus($transaction['financialStatement']['status']);
                            $payment->setAdditionalData(serialize($transaction));
                            $payment->save();

                            if ($order->canInvoice()) {
                                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                                $invoice = Mage::getModel('sales/service_order', $order)
                                ->prepareInvoice()
                                    ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
                                    ->register()
                                    ->pay();

                                $invoice->setEmailSent(true);
                                $invoice->getOrder()->setIsInProcess(true);

                                Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                    ->addObject($invoice->getOrder())
                                    ->save();
                            }
                            break;
                    }
                }

                $this->getResponse()->setHttpResponseCode(200)
                    ->setBody(json_encode(array('code' => '200', 'message' => 'OK')));

            } catch (Exception $ex) {

                $this->getResponse()
                    ->setHttpResponseCode(500)
                    ->setBody(json_encode(array('code' => '500', 'message' => $ex->getMessage())));
                return;
            }

        }

    }

    public function paymentStatusAction(){

        if (!$this->getRequest()->isGet()) {
            $this->getResponse()->setHttpResponseCode(405);
            return;
        }

        $helper = Mage::helper('pix');

        $incrementId = $this->getRequest()->getParam('id');
        $paymentId = $this->getRequest()->getParam('pid');

        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        
        if (!$order->getId() || $order->getPayment()->getMethod() != 'pix' || md5($order->getPayment()->getEntityId()) != $paymentId) {
            $this->_forward('noRoute');
            return;
        }

        $isPaymentApproved = $order->getPayment()->getCcStatus() == 'APPROVED';
        $isCountDownExpired = $helper->isCountdownExpired($order->getCreatedAtDate());
        $isQrcodeExpired = $helper->isQrcodeExpired($order->getCreatedAtDate());

        if($isCountDownExpired && $isQrcodeExpired && !$isPaymentApproved && $order->canCancel()) {
            $order->cancel();
            $order->save();
        }

        $result = [
            'payment_approved' => $isPaymentApproved,
            'order_canceled' => $order->isCanceled(),
            'qrcode_expired' => $isCountDownExpired
        ];

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

    }
}