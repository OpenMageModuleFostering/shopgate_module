<?php
/**
 * User: pliebig
 * Date: 20.08.14
 * Time: 22:20
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * class to manipulate the order payment data with amazon payment data
 *
 * @package     Shopgate_Framework_Model_Payment_Amazon
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Payment_Amazon
{
    /**
     * create new order for amazon payment
     *
     * @param $quote            Mage_Sales_Model_Quote
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function createNewOrder($quote)
    {
        $convert     = Mage::getModel('sales/convert_quote');
        $transaction = Mage::getModel('core/resource_transaction');

        if ($quote->getCustomerId()) {
            $transaction->addObject($quote->getCustomer());
        }

        $transaction->addObject($quote);
        if ($quote->isVirtual()) {
            $order = $convert->addressToOrder($quote->getBillingAddress());
        } else {
            $order = $convert->addressToOrder($quote->getShippingAddress());
        }
        $order->setBillingAddress($convert->addressToOrderAddress($quote->getBillingAddress()));
        if ($quote->getBillingAddress()->getCustomerAddress()) {
            $order->getBillingAddress()->setCustomerAddress($quote->getBillingAddress()->getCustomerAddress());
        }
        if (!$quote->isVirtual()) {
            $order->setShippingAddress($convert->addressToOrderAddress($quote->getShippingAddress()));
            if ($quote->getShippingAddress()->getCustomerAddress()) {
                $order->getShippingAddress()->setCustomerAddress($quote->getShippingAddress()->getCustomerAddress());
            }
        }
        $order->setPayment($convert->paymentToOrderPayment($quote->getPayment()));
        $order->getPayment()->setTransactionId($quote->getPayment()->getTransactionId());
        $order->getPayment()->setAdditionalInformation('amazon_order_reference_id', $quote->getPayment()
                                                                                          ->getTransactionId());

        foreach ($quote->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $orderItem = $convert->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }
        $order->setQuote($quote);
        $order->setExtOrderId($quote->getPayment()->getTransactionId());
        $order->setCanSendNewEmailFlag(false);

        $transaction->addObject($order);
        $transaction->addCommitCallback(array($order, 'save'));

        Mage::dispatchEvent('checkout_type_onepage_save_order', array('order' => $order, 'quote' => $quote));
        Mage::dispatchEvent('sales_model_service_quote_submit_before', array('order' => $order, 'quote' => $quote));

        try {
            $transaction->save();
            Mage::dispatchEvent('sales_model_service_quote_submit_success', array(
                'order' => $order,
                'quote' => $quote
            ));
        } catch (Exception $e) {
            //reset order ID's on exception, because order not saved
            $order->setId(null);
            /** @var $item Mage_Sales_Model_Order_Item */
            foreach ($order->getItemsCollection() as $item) {
                $item->setOrderId(null);
                $item->setItemId(null);
            }

            Mage::dispatchEvent('sales_model_service_quote_submit_failure', array(
                'order' => $order,
                'quote' => $quote
            ));
            throw $e;
        }
        Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));
        Mage::dispatchEvent('sales_model_service_quote_submit_after', array('order' => $order, 'quote' => $quote));

        return $order;
    }

    /**
     * @param $order            Mage_Sales_Model_Order
     * @param $shopgateOrder    ShopgateOrder
     *                          // TODO Refund
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order, $shopgateOrder)
    {
        $paymentInfos = $shopgateOrder->getPaymentInfos();
        try {
            $orderTrans = Mage::getModel('sales/order_payment_transaction');
            $orderTrans->setOrderPaymentObject($order->getPayment());
            $orderTrans->setIsClosed(false);
            $orderTrans->setTxnId($paymentInfos['mws_order_id']);
            $orderTrans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
            $orderTrans->save();
            $order->getPayment()->importTransactionInfo($orderTrans);
            $order->getPayment()->setLastTransId($paymentInfos['mws_order_id']);

            if (!empty($paymentInfos['mws_auth_id'])) {
                $authTrans = Mage::getModel('sales/order_payment_transaction');
                $authTrans->setOrderPaymentObject($order->getPayment());
                $authTrans->setParentTxnId($orderTrans->getTxnId(), $paymentInfos['mws_auth_id']);
                $authTrans->setIsClosed(false);
                $authTrans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                $authTrans->save();
                $order->getPayment()->importTransactionInfo($authTrans);
                $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                $order->getPayment()->setLastTransId($paymentInfos['mws_auth_id']);

                if (!empty($paymentInfos['mws_capture_id'])) {
                    $transaction = Mage::getModel('sales/order_payment_transaction');
                    $transaction->setOrderPaymentObject($order->getPayment());
                    $transaction->setParentTxnId($authTrans->getTxnId(), $paymentInfos['mws_capture_id']);
                    $transaction->setIsClosed(false);
                    $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                    $transaction->save();
                    $order->getPayment()->importTransactionInfo($transaction);
                    $order->getPayment()->capture(null);
                    /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
                    $invoiceCollection = $order->getInvoiceCollection();
                    $invoiceCollection->getFirstItem()->setTransactionId($paymentInfos['mws_capture_id']);
                    $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                    $order->getPayment()->setBaseAmountAuthorized($order->getBaseTotalDue());
                    $order->getPayment()->setLastTransId($paymentInfos['mws_capture_id']);
                }
            }
        } catch (Exception $x) {
            Mage::logException($x);
        }

        return $order;
    }

    /**
     * @param $quote    Mage_Sales_Model_Quote
     * @param $payment  Mage_Payment_Model_Method_Abstract|Creativestyle_AmazonPayments_Model_Payment_Advanced
     * @param $info     array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $payment, $info)
    {
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
        } else {
            $quote->getShippingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
        }

        $data = array( 'method' => $payment->getCode(),
                       'checks' => Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_COUNTRY
                                   | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_CURRENCY
                                   | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
        );

        $quote->getPayment()->importData($data);
        $quote->getPayment()->setTransactionId($info['mws_order_id']);
        return $quote;
    }
}