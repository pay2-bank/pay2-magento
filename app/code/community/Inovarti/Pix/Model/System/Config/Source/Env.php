<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Model_System_Config_Source_Env
{
    const ENV_SANDBOX          = 1;
    const ENV_PRODUCTION       = 0;

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::ENV_PRODUCTION,
                'label' => Mage::helper('pix')->__('Production')
            )
        );
    }
}
