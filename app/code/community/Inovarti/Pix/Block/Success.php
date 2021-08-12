<?php

class Inovarti_Pix_Block_Success extends Mage_Checkout_Block_Onepage_Success
{
   
    public function getOrder() {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        $current_order = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('increment_id', $orderId);

        if ($current_order) {
            foreach ($current_order as $order) {
                $final = $order;
                break;
            }
        }

        return $final;
    }
}