<?php

/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */

class Inovarti_Pix_Block_Form_Pix extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pix/form/pix.phtml');
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

}
