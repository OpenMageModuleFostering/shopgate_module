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
 *
 * @coversDefaultClass Shopgate_Framework_Helper_Payment_Wspp
 */
class Shopgate_Framework_Test_Helper_Payment_Wspp extends EcomDev_PHPUnit_Test_Case
{
    /** @var Shopgate_Framework_Helper_Payment_Wspp $helper */
    protected $helper;

    /**
     * Sets up the express class on unit load
     */
    public function setUp()
    {
        $this->helper = Mage::helper('shopgate/payment_wspp');
    }

    /**
     * Tests just the necessary status settings. Other parts of
     * the method will be tested in other functions.
     *
     * @param bool   $pending  - whether order is pending
     * @param bool   $fraud    -  whether order is fraud
     * @param string $expected - expected state of order
     *
     * @covers       ::orderStatusManager
     * @dataProvider statusProvider
     */
    public function testOrderStatusManager($pending, $fraud, $expected)
    {
        $mageOrder = Mage::getModel('sales/order');
        $payment   = Mage::getModel('sales/order_payment');

        $payment->setIsTransactionPending($pending);
        $payment->setIsFraudDetected($fraud);
        $mageOrder->setPayment($payment);
        $this->helper->orderStatusManager($mageOrder);

        $this->assertEquals($expected, $mageOrder->getState());
    }

    /**
     * @see testOrderStatusManager for details
     * @return array
     */
    public function statusProvider()
    {
        return array(
            array(true, false, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW),
            array(false, false, Mage_Sales_Model_Order::STATE_PROCESSING),
            array(true, true, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW),
            array(false, true, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW),
        );
    }

    /**
     * Retrieves the verbiage to use in order history.
     *
     * @param string $status   - PayPal status
     * @param string $expected - expected output
     *
     * @covers       ::getActionByStatus
     * @dataProvider dataProvider
     */
    public function testGetActionByStatus($status, $expected)
    {
        $action = $this->helper->getActionByStatus($status);

        $this->assertEquals($expected, $action);
    }

    /**
     * @param $expectedStatus
     *
     * @dataProvider dataProvider
     */
    public function testGetPaypalStatus($expectedStatus)
    {
        $function  = 'getPaypal' . ucfirst($expectedStatus) . 'Status';
        $newStatus = $this->helper->$function();

        $this->assertEquals($expectedStatus, $newStatus);
    }

    /**
     * Garbage collection prep
     */
    public function tearDown()
    {
        unset($this->helper);
    }
}