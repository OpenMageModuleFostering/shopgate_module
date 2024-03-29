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
 * @group              Shopgate_Payment_Mws
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Mws_Mws15
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Mws_Mws15 extends Shopgate_Framework_Test_Model_Payment_Abstract
{
    const MODULE_CONFIG      = 'Creativestyle_AmazonPayments';
    const CLASS_SHORT_NAME   = 'shopgate/payment_simple_mws_mws15';
    const XML_CONFIG_ENABLED = 'amazonpayments/general/active';

    /** @var Shopgate_Framework_Model_Payment_Simple_Mws_Mws15 $class */
    protected $class;

    /**
     * Test if transaction of mws15 returns const 'payment'
     *
     * @covers ::_getTransactionType
     */
    public function testGetTransactionType()
    {
        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_getTransactionType');
        $method->setAccessible(true);
        $result = $method->invoke($this->class, null);

        $this->assertEquals($result, 'payment');
    }

    /**
     * Rewrite to avoid issues with setOrderStatus method
     *
     * @param string $state
     *
     * @uses         Shopgate_Framework_Test_Model_Payment_Simple_Mws_Mws15::setPaidStatusFixture
     * @covers      ::setOrderStatus
     *
     * @dataProvider allStateProvider
     */
    public function testSetOrderStatus($state)
    {
        $this->setPaidStatusFixture($state);
        $order = Mage::getModel('sales/order');
        $this->class->setOrderStatus($order);
        $this->assertEquals(Mage_Sales_Model_Order::STATE_PROCESSING, $order->getState());
    }
}