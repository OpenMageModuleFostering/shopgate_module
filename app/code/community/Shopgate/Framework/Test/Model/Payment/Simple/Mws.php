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
 * @group              Shopgate_Payment_Mws
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Mws
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Mws extends Shopgate_Framework_Test_Model_Payment_RouterAbstract
{
    const CLASS_SHORT_NAME = 'shopgate/payment_simple_mws';

    /** @var Shopgate_Framework_Model_Payment_Simple_Mws $router */
    protected $router;

    /**
     * Check for mage < v1.6 re-route
     *
     * @uses   ShopgateOrder::setPaymentMethod
     * @uses   Shopgate_Framework_Model_Payment_Simple_Mws::getShopgateOrder
     * @uses   Shopgate_Framework_Model_Payment_Simple_Mws::getPaymentMethod
     * @uses   Shopgate_Framework_Model_Payment_Simple_Mws::setPaymentMethod
     *
     * @covers ::getModelByPaymentMethod
     */
    public function testGetMwsBelowMage16Method()
    {
        $configMock = $this->getHelperMock('shopgate/config', array('getIsMagentoVersionLower16'));
        $configMock->expects($this->exactly(2))
                   ->method('getIsMagentoVersionLower16')
                   ->willReturnOnConsecutiveCalls(false, true);
        $this->replaceByMock('helper', 'shopgate/config', $configMock);

        $this->router->getShopgateOrder()->setPaymentMethod(ShopgateCartBase::AMAZON_PAYMENT);
        $this->router->getModelByPaymentMethod();
        $this->assertEquals('MWS', $this->router->getPaymentMethod());

        $this->router->setPaymentMethod('');
        $this->router->getModelByPaymentMethod();
        $this->assertEquals('MWS15', $this->router->getPaymentMethod());
    }
}