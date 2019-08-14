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
 * @group              Shopgate_Payment_Cc
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Cc
 */
class Shopgate_Framework_Test_Model_Payment_Cc extends Shopgate_Framework_Test_Model_Payment_RouterAbstract
{
    const CLASS_SHORT_NAME = 'shopgate/payment_cc';

    /**
     * @var Shopgate_Framework_Model_Payment_Cc $router
     */
    protected $router;

    /**
     * Check that regular Authorize will run when the other authorize
     * plugins are disabled
     *
     * @uses   ShopgateOrder::setPaymentMethod
     * @uses   Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses   Shopgate_Framework_Model_Payment_Cc::getShopgateOrder
     * @uses   Shopgate_Framework_Model_Payment_Cc::getPaymentMethod
     *
     * @covers ::getModelByPaymentMethod
     */
    public function testGetAuthnCcPaymentMethod()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Authn::MODULE_CONFIG);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Cc_Authncim::MODULE_CONFIG);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Cc_Chargeitpro::MODULE_CONFIG);

        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::AUTHN_CC);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals(ShopgateCartBase::AUTHN_CC, $this->router->getPaymentMethod());
    }

    /**
     * Checks that we route to Autorize CIM when it's enabled and API returns true.
     * API calls is mocked and forced to return true.
     *
     * @uses   ShopgateOrder::setPaymentMethod
     * @uses   Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::enableModule
     * @uses   Shopgate_Framework_Model_Payment_Cc::getShopgateOrder
     * @uses   Shopgate_Framework_Model_Payment_Cc::getPaymentMethod
     *
     * @covers ::getModelByPaymentMethod
     */
    public function testGetAuthCimPaymentMethod()
    {
        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::AUTHN_CC);
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Authn::MODULE_CONFIG);
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Authncim::MODULE_CONFIG);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Cc_Chargeitpro::MODULE_CONFIG);
        $this->enableModule(Shopgate_Framework_Model_Payment_Cc_Authncim::XML_CONFIG_ENABLED);

        $mock = $this->getModelMock(
            'shopgate/payment_cc_authncim',
            array('checkGenericValid'),
            false,
            array(array($this->router->getShopgateOrder()))
        );
        $mock->expects($this->once())
             ->method('checkGenericValid')
             ->will($this->returnValue(true));

        $this->replaceByMock('model', 'shopgate/payment_cc_authncim', $mock);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals('AUTHNCIM_CC', $this->router->getPaymentMethod());
    }

    /**
     * When ChargeItPro is disabled, we default to USAePay
     *
     * @uses   ShopgateOrder::setPaymentMethod
     * @uses   Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::enableModule
     * @uses   Shopgate_Framework_Model_Payment_Cc::getShopgateOrder
     * @uses   Shopgate_Framework_Model_Payment_Cc::getPaymentMethod
     *
     * @covers ::getModelByPaymentMethod
     */
    public function testGetUsaEpayPaymentMethod()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Usaepay::MODULE_CONFIG);
        $this->enableModule(Shopgate_Framework_Model_Payment_Cc_Usaepay::XML_CONFIG_ENABLED);
        $this->deactivateModule(Shopgate_Framework_Model_Payment_Cc_Chargeitpro::MODULE_CONFIG);
        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::USAEPAY_CC);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals(ShopgateCartBase::USAEPAY_CC, $this->router->getPaymentMethod());
    }

    /**
     * Even if Mage_USAePay is enabled, ignore it and redirect to ChargeItPro
     *
     * @uses   ShopgateOrder::setPaymentMethod
     * @uses   Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::deactivateModule
     * @uses   Shopgate_Framework_Test_Model_Utility::enableModule
     * @uses   Shopgate_Framework_Model_Payment_Cc::getShopgateOrder
     * @uses   Shopgate_Framework_Model_Payment_Cc::getPaymentMethod
     *
     * @covers ::getModelByPaymentMethod
     */
    public function testGetChargeItProPaymentMethod()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Usaepay::MODULE_CONFIG);
        $this->enableModule(Shopgate_Framework_Model_Payment_Cc_Usaepay::XML_CONFIG_ENABLED);
        $this->activateModule(Shopgate_Framework_Model_Payment_Cc_Chargeitpro::MODULE_CONFIG);
        $this->enableModule(Shopgate_Framework_Model_Payment_Cc_Chargeitpro::XML_CONFIG_ENABLED);
        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::USAEPAY_CC);
        $this->router->getModelByPaymentMethod();

        $this->assertEquals('CHARGEITPRO_CC', $this->router->getPaymentMethod());
    }
}