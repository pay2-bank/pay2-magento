<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Se ativo para criar o log
     *
     * @return mixed
     */
    public function isDebug()
    {
        return Mage::getStoreConfig('payment/pix/debug_mode');
    }

    /**
     * criptografa os dados
     */
    public function encode($incrementId)
    {
        return $this->urlEncode(Mage::helper('core')->encrypt(base64_encode($incrementId)));
    }
    /**
     * descriptografa os dados
     */
    public function decode($encoded)
    {
        return $this->urlDecode(Mage::helper('core')->decrypt(base64_decode($encoded)));
    }

    /**
     * Verifica se esta ativo
     *
     * @return mixed
     */
    public function isActive()
    {
        return Mage::getStoreConfig('payment/pix/active');
    }

    public function getEnv()
    {
        return Mage::getStoreConfig('payment/pix/env');
    }

    public function getType()
    {
        return Mage::getStoreConfig('payment/pix/type');
    }


    public function getQrcodeContent()
    {
        return Mage::getStoreConfig('payment/pix/qrcode_content');
    }

    public function isProduction()
    {
        return !$this->getEnv();
    }

    public function getAccountId()
    {
        if ($this->getEnv()) {
            return Mage::getStoreConfig('payment/pix/account_id1');
        }
        return Mage::getStoreConfig('payment/pix/account_id');
    }

    public function getClientId()
    {
        if ($this->getEnv()) {
            return Mage::getStoreConfig('payment/pix/client_id1');
        }
        return Mage::getStoreConfig('payment/pix/client_id');
    }

    public function getClientSecret()
    {
        if ($this->getEnv()) {
            return Mage::getStoreConfig('payment/pix/client_secret1');
        }
        return Mage::getStoreConfig('payment/pix/client_secret');
    }

    public function getAccessToken()
    {
        if ($this->getEnv()) {
            return Mage::getStoreConfig('payment/pix/access_token1');
        }
        return Mage::getStoreConfig('payment/pix/access_token');
    }

    public function getKey()
    {
        return Mage::getStoreConfig('payment/pix/key');
    }

    public function isCancelOrderActive()
    {
        return Mage::getStoreConfig('payment/pix/cancel_order_active');
    }

    public function getCountdownTimeInSeconds()
    {
        return ((int) ($this->getExpireTime() < 20)?$this->getExpireTime() : $this->getExpireTime() - 10 ) * 60;
    }

    public function getClient()
    {
       return Mage::getModel('pix/client_pay2');
    }

    public function getExpireTime()
    {
        return Mage::getStoreConfig('payment/pix/expire_time');
    }

    public function getExpireTimeInSeconds()
    {
        return ((int) $this->getExpireTime()) * 60;
    }
    
    public function isQrcodeExpired(Zend_Date $orderCreatedAt) {

        $diffInSeconds = round((time() - $orderCreatedAt->getTimestamp()));

        // Adiciona 1 minuto para caso ocorra delay no callback
        return ($diffInSeconds > ($this->getExpireTimeInSeconds() + 60));
    }

    public function isCountdownExpired(Zend_Date $orderCreatedAt) {

        $diffInSeconds = round((time() - $orderCreatedAt->getTimestamp()));

        return ($diffInSeconds > ($this->getCountdownTimeInSeconds()));
    }

    public function getMessageError() {
        return Mage::getStoreConfig('payment/pix/message_error');
    }

    /**
     * Verificar status do boleto/cartao
     *
     * @return mixed
     */
    public function isCronAutorize()
    {
        return Mage::getStoreConfig('payment/pix/cron_autorize');
    }

    /**
     * Notificar o clientes antes de expirar
     *
     * @return mixed
     */
    public function isSendEmail()
    {
        return Mage::getStoreConfig('payment/pix/send_email');
    }

    /**
     * Format Number
     *
     * @param $number
     * @return string
     */
    public function _formatNumber($number)
    {
        $number = Mage::getSingleton('core/locale')->getNumber($number);
        return (float) sprintf('%0.2f', $number);
    }

    public function log($message, $file = "pix.log")
    {
        if ($this->isDebug()) {
            Mage::log($message, null, $file, true);
        }
    }

    /**
     * enviar email
     *
     * @param $to
     * @param Mage_Sales_Model_Order $order
     * @param $templateConfigPath
     * @param array $vars
     * @return $this|void
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function _sendEmail($to, Mage_Sales_Model_Order $order, $templateConfigPath, $vars = array())
    {
        // if (!$to || !$templateConfigPath) {
        //     return;
        // }

        // $translate = Mage::getSingleton('core/translate');
        // /* @var $translate Mage_Core_Model_Translate */
        // $translate->setTranslateInline(false);

        // $mailTemplate = Mage::getModel('core/email_template');
        // /* @var $mailTemplate Mage_Core_Model_Email_Template */

        // $template   = Mage::getStoreConfig($templateConfigPath, $order->getStore()->getId());
        // $name       = $order->getCustomerFirstname();

        // $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStore()->getId()));


        // $mailTemplate->sendTransactional($template, Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, $order->getStore()->getId()), $to, $name, $vars);

        // if (!$mailTemplate->getSentSuccess()) {
        //     throw new Exception('Não pode enviar o email');
        // }

        // $translate->setTranslateInline(true);

        return $this;
    }


    public function array_flatten($array, $prefix = null)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value, ($prefix ? $prefix . '_' . $key : $key)));
            } else {
                $result[($prefix ? $prefix . '_' . $key : $key)] = $value;
            }
        }
        return $result;
    } 
}
