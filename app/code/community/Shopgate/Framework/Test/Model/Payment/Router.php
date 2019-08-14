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
 * @group              Shopgate_Payment_Router
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Router
 */
class Shopgate_Framework_Test_Model_Payment_Router extends Shopgate_Framework_Test_Model_Payment_RouterAbstract
{
    const CLASS_SHORT_NAME = 'shopgate/payment_router';

    /** @var Shopgate_Framework_Model_Payment_Router $router */
    protected $router;

    /**
     * Check that constructor cannot be
     * initialized without a Shopgate object
     *
     * @expectedException Exception
     * @expectedExceptionMessage Incorrect class provided to: Shopgate_Framework_Model_Payment_Abstract::_constructor()
     */
    public function testConstructor()
    {
        Mage::getModel('shopgate/payment_abstract');
    }

    /**
     * Test getting parts of a payment method
     *
     * @param int    $partNumber    - part number to grab from paymentMethod, .e.g "PP"
     * @param string $paymentMethod - arrives from Shopgate API, e.g. PP_WSPP_CC
     * @param string $expected      - expected payment method part returned
     *
     * @uses         ShopgateOrder::setPaymentMethod
     * @uses         Shopgate_Framework_Model_Payment_Router::getShopgateOrder
     * @uses         Shopgate_Framework_Model_Payment_Router::getClassFromMethod
     * @covers       ::_getMethodPart
     *
     * @dataProvider dataProvider
     */
    public function testMethodPart($partNumber, $paymentMethod, $expected)
    {
        $this->router->getShopgateOrder()->setPaymentMethod($paymentMethod);
        $reflection = new ReflectionClass($this->router);
        $property   = $reflection->getProperty('_payment_method_part');
        $property->setAccessible(true);
        $property->setValue($this->router, $partNumber);
        $method = $reflection->getMethod('_getMethodPart');
        $method->setAccessible(true);
        $part = $method->invoke($this->router, null);

        $this->assertEquals($expected, $part);
    }

    /**
     * Should return the correct truncated class short name
     *
     * @covers ::getCurrentClassShortName
     */
    public function testGetClassShortName()
    {
        $reflection = new ReflectionClass($this->router);
        $method     = $reflection->getMethod('getCurrentClassShortName');
        $method->setAccessible(true);
        $className = $method->invoke($this->router, null);

        $this->assertEquals('shopgate/payment', $className);
    }

    /**
     * Tests backup class name path generator
     *
     * @param string $paymentMethod - e.g AUTHN_CC
     * @param array  $expected      - array of expected combinations. Has to match exactly.
     *
     * @uses         Shopgate_Framework_Model_Payment_Router::setPaymentMethod
     * @covers       ::_getModelCombinations
     *
     * @dataProvider dataProvider
     */
    public function testGetModelCombinations($paymentMethod, $expected)
    {
        $this->router->setPaymentMethod($paymentMethod);
        $reflection = new ReflectionClass($this->router);
        $method     = $reflection->getMethod('_getModelCombinations');
        $method->setAccessible(true);
        $combinations = $method->invoke($this->router, null);
        $this->assertArraySubset($expected, $combinations);
    }
}
