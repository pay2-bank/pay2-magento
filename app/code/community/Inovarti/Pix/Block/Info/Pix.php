<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Block_Info_Pix extends Mage_Payment_Block_Info
{

    protected $_brcode;
    protected $_brcodetext;
    protected $_notificationUrl;
    protected $_status;
    protected $_transactionDate;
    protected $_approveUrl;
    protected $_isApproved;
    protected $_isCanceled;
    protected $_instructions;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pix/info/pix.phtml');
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }
        return $this->_instructions;
    }

    public function getBRCode()
    {
        if (is_null($this->_brcode)) {
            $this->_convertAdditionalData();
        }
        return $this->_brcode;
    }
    
    public function getTransactionDate()
    {
        if (is_null($this->_transactionDate)) {
            $this->_convertAdditionalData();
        }
        return $this->_transactionDate;
    }
    
    public function getApproveUrl()
    {
        if (is_null($this->_approveUrl)) {
            $this->_convertAdditionalData();
        }
        return $this->_approveUrl;
    }
    
    public function getIsApproved()
    {
        if (is_null($this->_isApproved)) {
            $this->_convertAdditionalData();
        }
        return $this->_isApproved;
    }

    /**
     * @return string
     */
    public function getBRCodeText()
    {
        if (is_null($this->_brcodetext)) {
            $this->_convertAdditionalData();
        }
        return $this->_brcodetext;
    }

    public function getStatus()
    {
        if (is_null($this->_status)) {
            $this->_convertAdditionalData();
        }
        return $this->_status;
    }

    public function isCanceled()
    {
        if (is_null($this->_isCanceled)) {
            $this->_convertAdditionalData();
        }
        return $this->_isCanceled;
    }

    public function getOrderId() {
        if (is_null($this->_orderId)) {
            $this->_convertAdditionalData();
        }
        return $this->_orderId;
    }

    /**
     *
     * @return Inovarti_Pix_Block_Info_Pix
     */
    protected function _convertAdditionalData()
    {
        $details = false;
        try {
            $details = $this->getInfo()->getAdditionalInformation();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $order = $this->getInfo()->getOrder();

        if($order) {
            $payment = $order->getPayment();
            $helper = $this->helper('pix');
            
            $this->_brcode = $details['instantPayment']['generateImage']['imageContent']; // location itau
            $this->_brcodetext = $details['instantPayment']['textContent']; // content itau
            $this->_status = $details['financialStatement']['status']; // status itau
            $this->_transactionDate = $details['transactionDate'];
            $this->_approveUrl = Mage::getUrl('pix/notifications/paymentStatus', array('id' => $order->getIncrementId(), 'pid' => md5($payment->getEntityId())));
            $this->_isApproved = $order->getPayment()->getCcStatus() == 'APPROVED' ? '1' : '0';
            $this->_isCanceled = $order->isCanceled();
            $this->_orderId = $order->getIncrementId();
        }

        return $this;
    }
}
