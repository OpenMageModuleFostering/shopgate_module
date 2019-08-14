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
 * @author             Shopgate GmbH <interfaces@shopgate.com>
 * @author             Konstantin Kiritsenko <konstantin.kiritsenko@shopgate.com>
 * @group              Shopgate_Payment
 * @group              Shopgate_Payment_Shopgate
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Shopgate
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Shopgate extends Shopgate_Framework_Test_Model_Payment_Abstract
{
    const MODULE_CONFIG    = 'Shopgate_Framework';
    const CLASS_SHORT_NAME = 'shopgate/payment_simple_shopgate';

    /**
     * @var Shopgate_Framework_Model_Payment_Simple_Shopgate $class
     */
    protected $class;

    /**
     * Empty rewrite, Shopgate payment active
     * always returns true
     *
     * @group empty
     * @coversNothing
     */
    public function testIsDisabled() { }

    /**
     * State set in the database should match
     * the state that comes into the magento
     * order class
     *
     * @param string $state - mage order state, e.g. 'processing'
     *
     * @uses         ShopgateOrder::setIsShippingBlocked
     * @uses         Shopgate_Framework_Model_Payment_Simple_Shopgate::getShopgateOrder
     * @covers       ::setOrderStatus
     *
     * @dataProvider allStateProvider
     */
    public function testSetOrderStatus($state)
    {
        Mage::app()->getStore(0)->setConfig('payment/shopgate/order_status', $state);
        /** @var Mage_Sales_Model_Order $mageOrder */
        $mageOrder = Mage::getModel('sales/order');
        $this->class->getShopgateOrder()->setIsShippingBlocked(true);
        $this->class->setOrderStatus($mageOrder);

        $this->assertEquals(
            $state,
            $mageOrder->getState(),
            "State should be set to '{$state}, but it was '{$mageOrder->getState()}'"
        );
    }

    /**
     * Check that all order statuses lead to
     * "processing" when shipping is not blocked
     *
     * @param string $state - mage order state, e.g. 'processing'
     *
     * @uses         ShopgateOrder::setIsShippingBlocked
     * @uses         Shopgate_Framework_Model_Payment_Simple_Shopgate::getShopgateOrder
     * @covers       ::setOrderStatus
     *
     * @dataProvider allStateProvider
     */
    public function testGetOrderStatusWhenNotBlocked($state)
    {
        $mageOrder = Mage::getModel('sales/order');
        $mageOrder->setState($state);
        $this->class->getShopgateOrder()->setIsShippingBlocked(false);
        $this->class->setOrderStatus($mageOrder);

        $this->assertEquals(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            $mageOrder->getState(),
            'Status should always be "processing" when not blocked'
        );
    }
}