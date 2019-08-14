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
 * @group              Shopgate_Payment_Cc
 *                     
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Cc_Authn
 */
class Shopgate_Framework_Test_Model_Payment_Cc_Authn extends Shopgate_Framework_Test_Model_Payment_Abstract
{
    const CLASS_SHORT_NAME   = 'shopgate/payment_cc_authn';
    const MODULE_CONFIG      = 'Mage_Paygate';
    const XML_CONFIG_ENABLED = 'payment/authorizenet/active';

    /** @var Shopgate_Framework_Model_Payment_Cc_Authn $class */
    protected $class;

    /**
     * Blank rewrite, we will test this in abstract class
     *
     * @coversNothing
     */
    public function testShopgateStatusSet() { }

    /**
     * @uses ReflectionClass::getProperty
     * @covers ::_initVariables()
     */
    public function testInitVariables()
    {
        $order = new ShopgateOrder();
        $order->setPaymentInfos(
            array(
                'transaction_type' => 'test_type',
                'response_code'    => 'test_response',
            )
        );
        $this->class->setShopgateOrder($order);
        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_initVariables');
        $method->setAccessible(true);
        $method->invoke($this->class, null);
        $type = $reflection->getProperty('_transactionType');
        $type->setAccessible(true);
        $response = $reflection->getProperty('_responseCode');
        $response->setAccessible(true);

        $this->assertEquals('test_type', $type->getValue($this->class));
        $this->assertEquals('test_response', $response->getValue($this->class));
    }

    /**
     * @uses Shopgate_Framework_Model_Payment_Cc_Authn::setOrder
     * @covers ::_createTransaction
     */
    public function testCreateTransactions()
    {
        /**
         * Setup
         */
        $transId  = '123';
        $transKey = 'payment';

        $mock = $this->getModelMock('sales/order_payment_transaction', array('save'));
        $mock->method('save')
             ->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/order_payment_transaction', $mock);

        $order   = Mage::getModel('sales/order');
        $payment = Mage::getModel('sales/order_payment');
        $payment->setCcTransId($transId);
        $order->setPayment($payment);
        $this->class->setOrder($order);

        /**
         * Invoke
         *
         * @var Mage_Sales_Model_Order_Payment_Transaction $mock
         */
        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_createTransaction');
        $method->setAccessible(true);
        $method->invoke($this->class, $transKey, array('additional1' => 'Rnd'));
        $info = $mock->getAdditionalInformation();

        $this->assertEquals($transId, $mock->getTxnId());
        $this->assertEquals($transKey, $mock->getTxnType());
        $this->assertEquals(0, $mock->getIsClosed());
        $this->assertEquals($transId, $info['real_transaction_id']);
        $this->assertArrayHasKey('additional1', $info);
    }
}