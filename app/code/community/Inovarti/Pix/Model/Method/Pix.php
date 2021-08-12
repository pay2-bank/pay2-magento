<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright 2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Model_Method_Pix extends Inovarti_Pix_Model_Method_Abstract
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'pix';

    /**
     * Bank Transfer payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'pix/form_pix';
    protected $_infoBlockType = 'pix/info_pix';

    protected $_canOrder  = true;

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }

        $helper = Mage::helper('pix');

        if ($payment->getCcStatus() == 'APPROVED') {
            $transaction = unserialize($payment->getAdditionalData());
        } else {

            if($payment->getData()['additional_information']['instantPayment']['type'] == 'static') {
                $transaction = [
                    'transactionId' => $payment->getData()['additional_information']['transactionId'],
                    'financialStatement' => [
                        'status' => 'APPROVED'
                    ]
                ];
            } else {
                $transactionId = $payment->getData()['additional_information']['transactionId'];
                $transaction = $helper->getClient()->find($transactionId)['callback_raw_data'];
            }
        }

        if ($transaction) {
            switch ($transaction['financialStatement']['status']) {
                case 'APPROVED':
                    $payment->setAdditionalData(serialize($transaction));
                    $payment->setIsTransactionClosed(1);
                    $payment->setTransactionId('approved_'.$transaction['transactionId']);
                    $payment->setCcStatus($transaction['financialStatement']['status']);
                    $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $helper->array_flatten($transaction));
                    $payment->save();
                    break;
                default:
                    Mage::throwException($helper->__('Pedido não pode ser Capturado na Pay2'));
                    return $this;
                    break;
            }
        }
        return $this;
    }

}
