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
 * @group              Shopgate_Payment_Cod
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Cod
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Cod extends Shopgate_Framework_Test_Model_Payment_RouterAbstract
{
    const CLASS_SHORT_NAME = 'shopgate/payment_simple_cod';

    /**
     * @var Shopgate_Framework_Model_Payment_Simple_Cod $router
     */
    protected $router;

    /**
     * Adding a payment method
     */
    public function setUp()
    {
        parent::setUp();
        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::COD);
    }

    /**
     * Native method can only be ran if magento is v1.7.0.0+
     * and all the other modules are disabled
     *
     * @uses    Mage::getVersion
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::setPaymentMethod
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::getPaymentMethod
     * @covers  Shopgate_Framework_Model_Payment_Simple_Cod::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetCodNativeMethod()
    {
        $configMock = $this->getHelperMock('shopgate/config', array('getIsMagentoVersionLower1700'));
        $configMock->expects($this->exactly(2))
                   ->method('getIsMagentoVersionLower1700')
                   ->willReturnOnConsecutiveCalls(
                       false,
                       true
                   );
        $this->replaceByMock('helper', 'shopgate/config', $configMock);

        $this->deactivateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Phoenix108::MODULE_CONFIG);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Msp::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Native::MODULE_CONFIG);

        $this->router->getModelByPaymentMethod();
        $this->assertEquals('Native', $this->router->getPaymentMethod());

        $this->router->setPaymentMethod('');
        $this->router->getModelByPaymentMethod();
        $this->assertEquals(ShopgateCartBase::COD, $this->router->getPaymentMethod());
    }

    /**
     * Checks if phoenix module redirect works.
     *
     * Intentionally left COD Native to be enabled
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::getPaymentMethod
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::setPaymentMethod
     * @covers  Shopgate_Framework_Model_Payment_Simple_Cod::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetCodPhoenixMethod()
    {
        $routerMock = $this->getModelMock(
            self::CLASS_SHORT_NAME,
            array('_getVersion'),
            false,
            array(array(new ShopgateOrder()))
        );
        $routerMock->expects($this->exactly(2))
                     ->method('_getVersion')
                     ->willReturnOnConsecutiveCalls(
                         '1.0.7',
                         '1.0.8'
                     );

        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Native::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Phoenix108::MODULE_CONFIG);

        /** @var Shopgate_Framework_Model_Payment_Simple_Cod $routerMock */
        $routerMock->setPaymentMethod('');
        $routerMock->getModelByPaymentMethod();
        $this->assertContains('Phoenix107', $routerMock->getPaymentMethod());

        $routerMock->setPaymentMethod('');
        $routerMock->getModelByPaymentMethod();
        $this->assertContains('Phoenix108', $routerMock->getPaymentMethod());
    }

    /**
     * MSP takes priority over others even when all others are enabled
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::getPaymentMethod
     * @covers  Shopgate_Framework_Model_Payment_Simple_Cod::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetCodMspMethod()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Msp::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Native::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Cod_Phoenix108::MODULE_CONFIG);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals('Msp', $this->router->getPaymentMethod());
    }

    /**
     * Test if all are disabled, routes to COD.
     *
     * @uses    Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses    Shopgate_Framework_Model_Payment_Simple_Cod::getPaymentMethod
     * @covers  Shopgate_Framework_Model_Payment_Simple_Cod::getModelByPaymentMethod
     *
     * @loadFixture
     */
    public function testGetNoCodMethod()
    {
        /** @var Shopgate_Framework_Model_Payment_Simple_Cod | EcomDev_PHPUnit_Mock_Proxy $mock */
        $mock = $this->getModelMock(
            'shopgate/payment_simple_cod',
            array('isModuleActive'),
            false,
            array(array($this->router->getShopgateOrder()))
        );
        $mock->expects($this->once())
             ->method('isModuleActive')
             ->will($this->returnValue(false));

        $mock->getModelByPaymentMethod();

        $this->assertEquals(ShopgateCartBase::COD, $mock->getPaymentMethod());
    }
}