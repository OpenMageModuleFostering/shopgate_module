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
 * @group              Shopgate_Payment_Prepay
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Prepay
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Prepay extends Shopgate_Framework_Test_Model_Payment_RouterAbstract
{
    const CLASS_SHORT_NAME = 'shopgate/payment_simple_prepay';

    /** @var Shopgate_Framework_Model_Payment_Simple_Prepay $router */
    protected $router;

    /**
     * Adding a payment method
     */
    public function setUp()
    {
        parent::setUp();
        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::PREPAY);
    }

    /**
     * Test that router redirects to Native when it's
     * enabled in the database & mage v1.7+, else defaults
     * to CheckMoneyOrder if magento is lower than 1.7
     *
     * @uses    Mage::getVersion
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Prepay::getPaymentMethod
     * @covers  ::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetPrepayNativeMethod()
    {
        $configMock = $this->getHelperMock('shopgate/config', array('getIsMagentoVersionLower1700'));
        $configMock->expects($this->exactly(2))
                   ->method('getIsMagentoVersionLower1700')
                   ->willReturnOnConsecutiveCalls(
                       false, true
                   );
        $this->replaceByMock('helper', 'shopgate/config', $configMock);

        $this->deactivateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Phoenix::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Native::MODULE_CONFIG);

        $this->router->getModelByPaymentMethod();
        $this->assertEquals('Native', $this->router->getPaymentMethod());

        $this->router->getModelByPaymentMethod();
        $this->assertEquals('Checkmo', $this->router->getPaymentMethod());
    }

    /**
     * Checks if phoenix module redirect works. No need for other Bank
     * modules to be disabled as it takes over.
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Prepay::getPaymentMethod
     * @covers  ::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetPrepayPhoenixMethod()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Phoenix::MODULE_CONFIG);
        $this->router->getModelByPaymentMethod();

        $this->assertContains('Phoenix', $this->router->getPaymentMethod());
    }

    /**
     * Test if all are enabled and Phoenix comes on top
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Prepay::getPaymentMethod
     * @covers  ::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetAllPrepayEnabled()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Phoenix::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Native::MODULE_CONFIG);

        $this->router->getModelByPaymentMethod();

        $this->assertContains('Phoenix', $this->router->getPaymentMethod());
    }

    /**
     * Test if all are disabled, routes to Prepay by default
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Prepay::getPaymentMethod
     * @covers  ::getModelByPaymentMethod
     */
    public function testGetNoPrepayMethod()
    {
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Phoenix::MODULE_CONFIG);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Simple_Prepay_Native::MODULE_CONFIG);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals(ShopgateCartBase::PREPAY, $this->router->getPaymentMethod());
    }
}