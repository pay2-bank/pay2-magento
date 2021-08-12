<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

abstract class Inovarti_Pix_Model_Client_Abstract
{

    protected $_authClient;

    abstract public function charge($order, $payment);

    abstract public function cancel($transactionId, $refundId);

    protected function _doRequest($requestPath, $httpMethod = Zend_Http_Client::GET, $params = array(), $headers = array())
    {

        $helper = $this->_getHelper();

        if (!$this->_authClient) {
            throw new Inovarti_Pix_Model_Client_Exception(
                $helper->__('Auth client don\'t initialized.')
            );
        }

        $uri = $this->_getEndpointUri($requestPath);

        $httpClient = new Zend_Http_Client($uri, array('timeout' => 60));

        $this->_authClient->authorization($httpClient);

        if(!empty($headers)) {
            $httpClient->setHeaders($headers);
        }

        switch ($httpMethod) {
            case Zend_Http_Client::GET:
                $httpClient->setParameterGet($params);
                break;
            case Zend_Http_Client::POST:
                // $httpClient->setParameterPost($params);
                $httpClient->setRawData(json_encode($params), 'application/json');
                break;
            case Zend_Http_Client::PUT:
                $httpClient->setRawData(json_encode($params), 'application/json');
                break;
            case Zend_Http_Client::DELETE:
                break;
            default:
                throw new Exception(
                    $helper->__('Required HTTP method is not supported.')
                );
        }

        $response = $httpClient->request($httpMethod);

        $helper->log($response->getStatus() . ' - ' . $response->getBody());

        if($response->isError()) {
            Mage::throwException($helper->getMessageError());
        }

        $decodedResponse = json_decode($response->getBody());

        return $decodedResponse;
    }

    private function _getEndpointUri($requestPath)
    {

        $helper = $this->_getHelper();

        $endpoint = $helper->isProduction() ? $this::ENDPOINT_URI : $this::SANDBOX_ENDPOINT_URI;
        $url = $endpoint . $requestPath;

        return $url;
    }

    protected function _getHelper()
    {
        return Mage::helper('pix');
    }

    /**
     * Retrieves Customer Document Number (CPF/CNPJ)
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getDocumentNumber(Mage_Sales_Model_Order $order)
    {

        $document = preg_replace("/[^0-9]/", "", $order->getCustomerTaxvat());
        if (strlen($document) < 11 && $document) {
            return str_pad($document, 11, 0, STR_PAD_LEFT);
        }
        return $document;
    }

    /**
     * Retrieves Type Document
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function _getTypeDocument(Mage_Sales_Model_Order $order)
    {
        $document = $this->_getDocumentNumber($order);
        if (strlen($document) > 11 && $document) {
            return 'cnpj';
        }
        return 'cpf';
    }

    /**
     * Retrieves Customer Last Name
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getCustomerLastName(Mage_Sales_Model_Order $order)
    {
        $document = preg_replace("/[^0-9]/", "", $order->getCustomerTaxvat());
        if (strlen($document) > 11 && $document) {
            return '';
        }
        return $order->getCustomerLastname();
    }

    /**
     * Retrieves Customer Name
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getCustomerName(Mage_Sales_Model_Order $order)
    {
        $document = preg_replace("/[^0-9]/", "", $order->getCustomerTaxvat());
        if (strlen($document) > 11 && $document) {
            return $order->getCustomerName();
        }
        return $order->getCustomerFirstname();
    }

    protected function _getAddress(Mage_Sales_Model_Order $order)
    {
        return $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
    }

    public function getItemsData(Mage_Sales_Model_Order $order)
    {

        $helper = Mage::helper('pix');
        $result = array();
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $image = (string)Mage::helper('catalog/image')->init($product, 'image');
            $result[] = array(
                "id" => $item->getSku(),
                "name" => $item->getName(),
                "image" => $image,
                "quantity" => intval($item->getQtyOrdered()),
                "sale_price" => $helper->_formatNumber($item->getBasePrice())
            );
        }

        return $result;
    }

    public function getShippingData($order)
    {

        /** @var $address Mage_Sales_Model_Order */
        $address    = $order->getShippingAddress();
        $helper = Mage::helper('pix');

        $data = [
            'name' => $this->_getShippingTitle($order),
            'amount' => $helper->_formatNumber($order->getShippingAmount()),
            'address' => $this->_getAddressData($address)
        ];

        return $data;
    }

    public function _getAddressData($address) {
        $data = [
            'street' => $this->_getAddressStreet($address),
            'number' => $this->_getAddressStreetNumber($address),
            'complement' => $this->_getAddressComplement($address),
            'neighborhood' => $this->_getAddressNeighborhood($address),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'postal_code' => $this->_getAddressPostalCode($address)
        ];
        return $data;
    }

    public function getPayerData($order) {

        /** @var $address Mage_Customer_Model_Address */
        $address = $this->_getAddress($order);

        $data = [
            'name' => $this->_getCustomerName($order),
            'email' => $address->getEmail(),
            'tax_id' => $this->_getDocumentNumber($order),
            'address' => $this->_getAddressData($address)
        ];

        return $data;
    }

    public function getPaymentData($order) {

        $helper = Mage::helper('pix');

        $storeId = $order->getStoreId();
        $storeName = Mage::app()->getStore($storeId)->getFrontendName();

        $data = [
            'account_id' => $helper->getAccountId(),
            'external_identifier' => $order->getIncrementId(),
            'total_amount' => $helper->_formatNumber($order->getBaseGrandTotal()),
            'expiration' => $helper->getExpireTimeInSeconds(),
            'message' => $helper->__("Order # %s in store %s", $order->getIncrementId(), $storeName),
            'store_name' => Mage::app()->getStore()->getName()
        ];

        return $data;
    }
        
    /**
     * Retrieves Address Street
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreet($address)
    {
        return $address->getStreet(1);
    }

    /**
     * Retrieves Address Street Number
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreetNumber($address)
    {
        return ($address->getStreet(2)) ? $address->getStreet(2) : 'SN';
    }

    /**
     * Retrieves Address Complement
     *
     * @param $address
     * @return string
     */
    protected function _getAddressComplement($address)
    {
        return $address->getStreet(3);
    }

    /**
     * Retrieves Address Neighborhood
     *
     * @param $address
     * @return string
     */
    protected function _getAddressNeighborhood($address)
    {
        return $address->getStreet(4);
    }

    /**
     * Retrieves Address Postal Code
     *
     * @param $address
     * @return string
     */
    protected function _getAddressPostalCode($address)
    {
        return preg_replace('/[^0-9]/', '', $address->getPostcode());
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function _getShippingTitle($order)
    {
        return $order->getShippingDescription();
    }

}
