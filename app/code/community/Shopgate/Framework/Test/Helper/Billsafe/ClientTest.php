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
 * @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * @coversDefaultClass Shopgate_Framework_Helper_Billsafe_Client
 */
class Shopgate_Framework_Test_Helper_Billsafe_ClientTest extends Shopgate_Framework_Test_Model_Utility
{
    /**
     * @uses Shopgate_Framework_Model_Shopgate_Order::load
     * @covers ::getShopgateOrderNumber
     */
    public function testGetShopgateOrderNumber()
    {
        $this->activateModule(Shopgate_Framework_Model_Payment_Simple_Billsafe::MODULE_CONFIG);

        /**
         * Setup for load call. Will return and empty self first, then the Varien object
         */
        $varien = new Varien_Object(array('shopgate_order_number' => '01234'));
        $order  = Mage::getModel('sales/order')->setIncrementId('5678');
        $mock   = $this->getModelMock('shopgate/shopgate_order');
        $mock->expects($this->any())
             ->method('load')
             ->willReturnOnConsecutiveCalls($this->returnSelf(), $varien, $varien);
        $this->replaceByMock('model', 'shopgate/shopgate_order', $mock);

        /**
         * Client class setup as the constructor is buggy
         */
        $client     = $this->getHelperMock('shopgate/billsafe_client', array(), false, array(), '', false);
        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('getShopgateOrderNumber');
        $method->setAccessible(true);

        $test = $method->invoke($client, $order);
        $this->assertEquals($order->getIncrementId(), $test);

        $test2 = $method->invoke($client, $order);
        $this->assertEquals('01234', $test2);

        /**
         * Test passing in Varien_Object to method signature, as long as there is no error, we are good
         */
        $this->assertNotEmpty($method->invoke($client, $varien));
    }
}