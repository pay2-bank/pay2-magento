<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Model_Client_Pay2 extends Inovarti_Pix_Model_Client_Abstract
{

    const ENDPOINT_URI = 'https://dev.pay2.com.br';
    const SANDBOX_ENDPOINT_URI = 'http://localhost';

    public function __construct()
    {

        $helper = $this->_getHelper();

        $this->_authClient = Mage::getModel('pix/oauth_pay2');
        $this->_authClient->setAccessKey($helper->getAccessToken());
    }

    public function charge($order, $payment)
    {

        $helper = $this->_getHelper();
        
        $data = [
            'payer' => $this->getPayerData($order),
            'payment' => $this->getPaymentData($order),
            'items' => $this->getItemsData($order),
            'shipping' => $this->getShippingData($order)
        ];

        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );

        $result = $this->_doRequest('/api/transactions', Zend_Http_Client::POST, $data, $headers);

        if (!$result || !$result->data->raw_data || !$result->data->raw_data->data->instantPayment || !$result->data->raw_data->data->instantPayment->generateImage->imageContent) {
            Mage::throwException($helper->getMessageError());
        }

        return json_decode(json_encode($result->data->raw_data->data), true);
    }

    public function cancel($transactionId, $refundId)
    {

        $response =  $this->_doRequest('/api/transactions/' . $transactionId . '/refund', Zend_Http_Client::POST, []);

        return json_decode(json_encode($response), true);
    }

    public function find($transactionId)
    {

        $headers = array(
            'Content-Type' => 'application/json'
        );

        $result = $this->_doRequest('/api/transactions/' . $transactionId, Zend_Http_Client::GET, array(), $headers);

        return json_decode(json_encode($result), true)['data'];
    }
}
