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
 * @coversDefaultClass Shopgate_Framework_Model_Payment_Cc_AuthnAbstract
 */
class Shopgate_Framework_Test_Model_Payment_Cc_AuthnAbstract extends EcomDev_PHPUnit_Test_Case
{
    /** @var Shopgate_Framework_Model_Payment_Cc_AuthnAbstract $class */
    protected $class;

    /**
     * Init coverable class mock
     */
    public function setUp()
    {
        $this->class = Mage::getModel('shopgate/payment_cc_authnAbstract', array(new ShopgateOrder()));
    }

    /**
     * Tests capture and hold scenarios when invoice is created
     *
     * @uses   ShopgateOrder::getPaymentInfos
     * @uses   Shopgate_Framework_Model_Payment_Cc_AuthnAbstract::_isOrderPendingReview
     * @uses   Shopgate_Framework_Helper_Payment_Abstract::createOrderInvoice
     * @uses   Shopgate_Framework_Model_Payment_Cc_AuthnAbstract::getShopgateOrder
     * @covers ::_createInvoice
     *
     * @after testOrderPendingReview
     */
    public function testCreateInvoice()
    {
        $transactionId = '123';
        $covered       = $this->getModelMock(
            'shopgate/payment_cc_authnAbstract',
            array('_isOrderPendingReview'),
            false,
            array(array(new ShopgateOrder()))
        );
        $covered->method('_isOrderPendingReview')
                ->willReturn(true);

        $order = $this->getModelMock('sales/order', array('addRelatedObject'));
        $this->replaceByMock('model', 'sales/order', $order);
        $payment = Mage::getModel('sales/order_payment');

        /**
         * @var Shopgate_Framework_Model_Payment_Cc_AuthnAbstract $covered
         * @var Mage_Sales_Model_Order                            $order
         */
        $order->setPayment($payment);
        $covered->setOrder($order);
        $covered->getShopgateOrder()->setPaymentInfos(array('transaction_id' => $transactionId));

        $invoice = $this->mockModel('sales/order_invoice', array('pay', 'save'));
        $this->replaceByMock('model', 'sales/order_invoice', $invoice);

        $helper = $this->mockHelper('shopgate/payment_abstract', array('createOrderInvoice'));
        $helper->method('createOrderInvoice')->willReturn($invoice->getMockInstance());
        $this->replaceByMock('helper', 'shopgate/payment_abstract', $helper);

        $reflection = new ReflectionClass($covered);
        $response   = $reflection->getProperty('_responseCode');
        $response->setAccessible(true);
        $txnType = $reflection->getProperty('_transactionType');
        $txnType->setAccessible(true);
        $method = $reflection->getMethod('_createInvoice');
        $method->setAccessible(true);

        /**
         * Invoke scenario 1, code approved, status auth_capture
         *
         * @var Mage_Sales_Model_Order_Invoice $invoice
         */
        $response->setValue($covered, $reflection->getConstant('RESPONSE_CODE_APPROVED'));
        $txnType->setValue($covered, $reflection->getConstant('SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE'));
        $method->invoke($covered, null);

        $this->assertTrue($invoice->getIsPaid());
        $this->assertEquals($transactionId, $invoice->getTransactionId());

        /**
         * Invoke scenario 2, code held, status pending review on gate
         */
        $response->setValue($covered, $reflection->getConstant('RESPONSE_CODE_HELD'));
        $method->invoke($covered, null);

        $this->assertFalse($invoice->getIsPaid());
        $this->assertEquals($transactionId, $invoice->getTransactionId());
    }


    /**
     * Testing order message setter & state
     *
     * @uses    Shopgate_Framework_Model_Payment_Cc_AuthnAbstract::_isOrderPendingReview
     * @covers  ::setOrderStatus
     *
     * @after   testOrderPendingReview
     */
    public function testSetOrderStatus()
    {
        $order = $this->getModelMock('sales/order', array('setState'));
        $this->replaceByMock('model', 'sales/order', $order);
        $order->method('setState')
              ->will(
                  $this->returnCallback(
                      function ($state, $status, $message) use (&$msg) {
                          $msg = array('state' => $state, 'message' => $message);
                      }
                  )
              );
        $covered = $this->getModelMock(
            'shopgate/payment_cc_authnAbstract',
            array('_isOrderPendingReview'),
            false,
            array(array(new ShopgateOrder()))
        );
        $covered->method('_isOrderPendingReview')
                ->willReturnOnConsecutiveCalls(
                    array(true, false)
                );

        $reflection = new ReflectionClass($covered);
        $response   = $reflection->getProperty('_responseCode');
        $response->setAccessible(true);
        $txnType = $reflection->getProperty('_transactionType');
        $txnType->setAccessible(true);

        /**
         * @var Shopgate_Framework_Model_Payment_Cc_AuthnAbstract $covered
         * @var Mage_Sales_Model_Order                            $order
         *
         * Response approved, but not yet captured
         */
        $covered->setOrder($order);
        $response->setValue($covered, $reflection->getConstant('RESPONSE_CODE_APPROVED'));
        $covered->setOrderStatus($order);
        $this->assertContains('Authorized', $msg['message']);

        /**
         * Response approved & captured
         */
        $txnType->setValue($covered, $reflection->getConstant('SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE'));
        $covered->setOrderStatus($order);
        $this->assertContains('Captured', $msg['message']);

        /**
         * Response on hold, is pending review
         */
        $response->setValue($covered, $reflection->getConstant('RESPONSE_CODE_HELD'));
        $covered->setOrderStatus($order);
        $this->assertContains('Capturing', $msg['message']);

        /**
         * Response on hold, but does not have a reason code
         */
        $covered->setOrderStatus($order);
        $this->assertEmpty($msg['message']);

        /**
         * Response held on gateway, error code provided
         */
        $covered->getShopgateOrder()->setPaymentInfos(array('response_reason_code' => 'code'));
        $covered->setOrderStatus($order);
        $this->assertContains('response reason', $msg['message']);

        /**
         * No response
         */
        $response->setValue($covered, '');
        $covered->setOrderStatus($order);
        $this->assertContains('response code', $msg['message']);

        /**
         * State always 'processing'
         */
        $this->assertEquals('processing', $msg['state']);
    }

    /**
     * Tests whether response on gateway is pending
     * by testing the code provided
     *
     * @uses    ShopgateOrder::setPaymentInfos
     * @covers  ::_isOrderPendingReview
     */
    public function testOrderPendingReview()
    {
        $order = new ShopgateOrder();
        $this->class->setShopgateOrder($order);

        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_isOrderPendingReview');
        $method->setAccessible(true);

        /**
         * Pending Authorization on gateway
         */
        $code = $reflection->getConstant('RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED');
        $order->setPaymentInfos(array('response_reason_code' => $code));
        $this->assertTrue($method->invoke($this->class, null));

        /**
         * Pending review on gateway
         */
        $code = $reflection->getConstant('RESPONSE_REASON_CODE_PENDING_REVIEW');
        $order->setPaymentInfos(array('response_reason_code' => $code));
        $this->assertTrue($method->invoke($this->class, null));

        /**
         * Unknown code
         */
        $order->setPaymentInfos(array('response_reason_code' => 123));
        $this->assertFalse($method->invoke($this->class, null));

        /**
         * No conditions
         */
        $order->setPaymentInfos(array());
        $this->assertFalse($method->invoke($this->class, null));
    }
}