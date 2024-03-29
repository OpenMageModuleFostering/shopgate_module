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
class Shopgate_Framework_Model_Factory
{
    /** @var null | Shopgate_Framework_Model_Payment_Factory */
    protected $paymentFactory = null;

    /** @var null | Shopgate_Framework_Model_Modules_Affiliate_Factory */
    protected $affiliateFactory = null;

    /**
     * Payment factory retriever
     *
     * @param ShopgateOrder | null $sgOrder
     *
     * @return Shopgate_Framework_Model_Payment_Factory
     */
    public function getPayment(ShopgateOrder $sgOrder = null)
    {
        if (is_null($this->paymentFactory)) {
            if (is_null($sgOrder)) {
                $sgOrder = Mage::getModel('core/session')->getShopgateOrder();
            }
            $this->paymentFactory = Mage::getModel('shopgate/payment_factory', array($sgOrder));
        }

        return $this->paymentFactory;
    }

    /**
     * Affiliate factory retriever
     *
     * @param ShopgateCartBase | null $sgOrder
     *
     * @return Shopgate_Framework_Model_Modules_Affiliate_Factory
     */
    public function getAffiliate(ShopgateCartBase $sgOrder = null)
    {
        if (is_null($this->affiliateFactory)) {
            if (is_null($sgOrder)) {
                $sgOrder = Mage::getModel('core/session')->getShopgateOrder();
            }
            $router                 = Mage::getModel('shopgate/modules_affiliate_router', array($sgOrder));
            $this->affiliateFactory = Mage::getModel('shopgate/modules_affiliate_factory', array($sgOrder, $router));
        }

        return $this->affiliateFactory;
    }
}