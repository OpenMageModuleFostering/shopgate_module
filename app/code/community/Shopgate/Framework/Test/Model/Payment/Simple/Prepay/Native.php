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
 * @group              Shopgate_Payment_Prepay
 *
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Simple_Prepay_Native
 */
class Shopgate_Framework_Test_Model_Payment_Simple_Prepay_Native extends Shopgate_Framework_Test_Model_Payment_Abstract
{
    const MODULE_CONFIG      = 'Mage_Payment';
    const CLASS_SHORT_NAME   = 'shopgate/payment_simple_prepay_native';
    const XML_CONFIG_ENABLED = 'payment/banktransfer/active';

    /** @var Shopgate_Framework_Model_Payment_Simple_Prepay_Native $class */
    protected $class;

    /**
     * We default to use magento's config
     *
     * @uses   Mage::getModel
     * @covers ::setOrderStatus
     */
    public function testSetOrderStatus()
    {
        $order = Mage::getModel('sales/order');
        $this->class->setOrderStatus($order);
        
        $this->assertNull($order->getState());
    }

    /**
     * Allow fallthrough so that old
     * status setter can assign status
     *
     * @group empty
     * @coversNothing
     */
    public function testShopgateStatusSet() { }
}