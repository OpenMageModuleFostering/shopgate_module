<?php

/**
 * Shopgate GmbH
 *
 * URHEBERRECHTSHINWEIS
 *
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 *
 * COPYRIGHT NOTICE
 *
 * This plugin is the subject of copyright protection. It is only for the use of Shopgate GmbH customers,
 * for the purpose of facilitating communication between the IT system of the customer and the IT system
 * of Shopgate GmbH via www.shopgate.com. Any reproduction, dissemination, public propagation, processing or
 * transfer to third parties is only permitted where we previously consented thereto in writing. The provisions
 * of paragraph 69 d, sub-paragraphs 2, 3 and paragraph 69, sub-paragraph e of the German Copyright Act shall remain unaffected.
 *
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/** @noinspection PhpIncludeInspection */
include_once Mage::getBaseDir("lib") . '/Shopgate/shopgate.php';

/**
 * Produces an info block for orders
 */
class Shopgate_Framework_Block_Payment_Notice extends Mage_Core_Block_Template
{
    /** @var Mage_Sales_Model_Order | Varien_Object */
    protected $order;

    /**
     * @return Mage_Sales_Model_Order|Varien_Object
     */
    public function getOrder()
    {
        if (is_null($this->order)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            } elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            } else {
                $order = new Varien_Object();
            }
            $this->order = $order;
        }
        return $this->order;
    }

    /**
     * @return Shopgate_Framework_Model_Shopgate_Order
     */
    public function getShopgateOrder()
    {
        return Mage::getModel('shopgate/shopgate_order')->load($this->getOrder()->getId(), 'order_id');
    }

    /**
     * Retrieves a warning if there is a difference between
     * actual Order total and Shopgate order total passed
     *
     * @return bool
     */
    public function hasDifferentPrices()
    {
        $order         = $this->getOrder();
        $shopgateOrder = $this->getShopgateOrder()->getShopgateOrderObject();

        if (!$shopgateOrder instanceof ShopgateOrder) {
            return false;
        }

        $isDifferent = !Mage::helper('shopgate')->isOrderTotalCorrect($shopgateOrder, $order, $msg);

        return $isDifferent;
    }

    /**
     * @see Shopgate_Framework_Block_Payment_MobilePayment::printHtmlError
     * @inheritdoc
     */
    public function printHtmlError($errorMessage)
    {
        /** @var Shopgate_Framework_Block_Payment_MobilePayment $mobile */
        $mobile = Mage::getBlockSingleton('shopgate/payment_MobilePayment');
        return $mobile->printHtmlError($errorMessage);
    }

}