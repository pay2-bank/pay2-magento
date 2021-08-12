<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

abstract class Inovarti_Pix_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{

    protected $_isGateway               = true;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund               = true;
    protected $_canCapture              = true;

    /*
    * Order payment abstract method
    *
    * @param Varien_Object $payment
    * @param float $amount
    *
    * @return Mage_Payment_Model_Abstract
    */
    public function order(Varien_Object $payment, $amount)
    {
        parent::order($payment, $amount);
        $this->_placeOrder();
        return $this;
    }

    /**
     * Get pix payment data
     *
     * @return array
     */
    public function _placeOrder()
    {

        try {

            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $helper = Mage::helper('pix');

            $result = $helper->getClient()->charge($order, $payment);

            if ($result) {
                $payment->setAdditionalInformation($result);
                $payment->setTransactionId('created_'.$result['transactionId']);
                $payment->setCcStatus($result['financialStatement']['status']);
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $helper->array_flatten($result));
                $payment->save();

                $order->addStatusHistoryComment($helper->__('PIX - Pedido Criado'));
                $order->save();
            }
        } catch (Inovarti_Pix_Model_Client_Exception $e) {
            Mage::throwException($helper->__($e->getMessage()));
            $helper->log('Inovarti_Pix_Model_Client_Exception: ' . $e->getMessage());
        } catch (Mage_Core_Exception $e) {
            Mage::throwException($helper->__($e->getMessage()));
            $helper->log('Mage_Core_Exception: ' . $e->getMessage());
        } catch (Exception $e) {
            Mage::throwException($helper->__($e->getMessage()));
            $helper->log('connection failed(T): ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * Attempt to accept a payment that us under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        return true;
    }

    /**
     * Attempt to deny a payment that us under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        return $this->void($payment);
    }

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }
        return $this->refund($payment, $payment->getOrder()->getBaseTotalDue());
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {

        if (!$this->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }
        
        $helper = Mage::helper('pix');

        $transactionId = $payment->getData()['additional_information']['transactionId'];

        $response = $helper->getClient()->cancel($transactionId, null);

        $helper->log(json_encode($response), 'pix_return_cancel_error.log');

        if ($response && $response['data'] && $response['data']['status'] == 'REFUNDED') {

            $payment
                ->setTransactionId('refunded_'.$transactionId)
                ->setCcStatus('REFUNDED')
                ->setIsTransactionClosed(1)
                ->setShouldCloseParentTransaction(1)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $helper->array_flatten($response['data']));
            
            $payment->save();

        } else {
            
            Mage::throwException(Mage::helper('pix')->__('Pedido não pode ser cancelado no Pix.'));
        }

        return $this;
    }

}
