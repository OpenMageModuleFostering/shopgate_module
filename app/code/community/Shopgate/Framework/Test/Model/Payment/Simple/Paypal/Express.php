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
 * @group              Shopgate_Payment_Paypal
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Paypal_Express
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Paypal_Express extends Shopgate_Framework_Test_Model_Payment_Abstract
{
    const MODULE_CONFIG      = 'Mage_Paypal';
    const CLASS_SHORT_NAME   = 'shopgate/payment_simple_paypal_express';
    const XML_CONFIG_ENABLED = 'payment/paypal_express/active';

    /**
     * @var Shopgate_Framework_Model_Payment_Simple_Paypal_Express $class
     */
    protected $class;

    /**
     * Checks only the status of that function,
     * not the parent. The response should always be true
     * when order is_paid flag is true
     *
     * @param string $state - magento sale order state
     *
     * @uses         ShopgateOrder::setIsPaid
     * @uses         Shopgate_Framework_Model_Payment_Simple_Paypal_Express::getShopgateOrder
     *
     * @covers       ::setOrderStatus
     * @dataProvider allStateProvider
     */
    public function testSetOrderStatus($state)
    {
        $this->class->getShopgateOrder()->setIsPaid(true);
        $mageOrder = Mage::getModel('sales/order');
        $payment   = Mage::getModel('sales/order_payment');
        $payment->setTransactionAdditionalInfo('raw_details_info', array('payment_status' => 'completed'));
        $mageOrder->setPayment($payment);
        $mageOrder->setState($state);
        $this->class->setOrderStatus($mageOrder);

        $this->assertEquals(Mage_Sales_Model_Order::STATE_PROCESSING, $mageOrder->getState());
    }

    /**
     * Rewrites the default method to include
     * the payment data checks
     *
     * @uses   Shopgate_Framework_Test_Model_Payment_Simple_Paypal_Express::setPaidStatusFixture
     *
     * @covers ::setOrderStatus
     */
    public function testShopgateStatusSet()
    {
        $this->setPaidStatusFixture('processing');
        $order   = Mage::getModel('sales/order');
        $payment = Mage::getModel('sales/order_payment');
        $payment->setTransactionAdditionalInfo('raw_details_info', array('payment_status' => 'completed'));
        $order->setPayment($payment);
        $this->class->setOrderStatus($order);
        $this->assertTrue($order->getShopgateStatusSet());
    }

    /**
     * Makes sure we are loading express class
     * if Standard is not enabled
     *
     * @uses   ShopgateOrder::setPaymentMethod
     *
     * @covers Shopgate_Framework_Model_Payment_Factory::calculatePaymentClass
     */
    public function testModelLoad()
    {
        Mage::app()->getStore(0)->setConfig('payment/paypal_standard/active', 0);
        $order = new ShopgateOrder();
        $order->setPaymentMethod(ShopgateCartBase::PAYPAL);
        /** @var Shopgate_Framework_Model_Payment_Factory $factory */
        $factory = Mage::getModel('shopgate/payment_factory', array($order));
        $model   = $factory->calculatePaymentClass();

        $this->assertInstanceOf('Shopgate_Framework_Model_Payment_Simple_Paypal_Express', $model);
    }

    /**
     * Checks if helper returns WSPP's helper.
     * We use a reflector class to access a protected method.
     * 
     * @covers ::_getPaymentHelper
     */
    public function testGetHelper()
    {
        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_getPaymentHelper');
        $method->setAccessible(true);
        $helper = $method->invoke($this->class, null);

        $this->assertInstanceOf('Shopgate_Framework_Helper_Payment_Wspp', $helper);
    }
}