<?php
class Inovarti_Pix_Model_Observer
{

    /**
     * Verifica pedidos com PIX vencido
     * @return bool
     * @throws Exception
     */
    public function cancelOrdersWithQrcodeExpired()
    {
        /** @var $helper Inovarti_Pix_Helper_Data */
        $helper = $this->_getHelper();

        if (!$helper->isActive() || !$helper->isCancelOrderActive()) {
            return;
        }

        $orders = Mage::getModel('sales/order')->getCollection();
        $orders->addAttributeToFilter('status', array('in' => array('pending')));
        $orders->getSelect()->joinLeft(array('payment_table' => 'sales_flat_order_payment'), "main_table.entity_id = payment_table.parent_id", array("method"), null);
        $orders->addAttributeToFilter('payment_table.method', array('in' => array('pix')));

        /** @var Mage_Sales_Model_Order $order */
        foreach ($orders as $order) {

             /** @var Mage_Sales_Model_Order $order */
             $order = Mage::getModel('sales/order')->load($order->getId());

            if($helper->isQrcodeExpired($order->getCreatedAtDate())) {

                $payment = $order->getPayment();
                $transactionId = $payment->getData()['additional_information']['transactionId'];

                $transaction = $helper->getClient()->find($transactionId);
                $transactionStatus = $transaction['status'];

                if($transactionStatus == 'CREATED' && $order->canCancel()) {

                    $order->cancel();
                    $order->save();

                } else if($transactionStatus == 'APPROVED' && $order->canInvoice()) {

                    $payment->setAdditionalData(serialize($transaction));
                    $payment->setCcStatus($transaction['status']);
                    $payment->save();

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
            }

        }

    }

    protected function _getHelper()
    {
        return Mage::helper('pix');
    }
}
