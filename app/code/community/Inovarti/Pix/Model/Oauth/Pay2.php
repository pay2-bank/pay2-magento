<?php
/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */
class Inovarti_Pix_Model_Oauth_Pay2 extends Inovarti_Pix_Model_Oauth_Authorization
{

    protected $accessKey = null;

    public function getAccessKey()
    {
        return $this->accessKey;
    }

    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    public function authorization($httpClient) {

        $httpClient->setHeaders('Authorization', 'Bearer ' . $this->getAccessKey());

        return $httpClient;
    }

}