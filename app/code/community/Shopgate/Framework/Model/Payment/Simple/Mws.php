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
 * Payment handler for Creativestyle_AmazonPayments
 *
 * Class Shopgate_Framework_Model_Payment_Simple_Mws
 *
 * @author  Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Mws
    extends Shopgate_Framework_Model_Payment_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::AMAZON_PAYMENT;
    const XML_CONFIG_ENABLED = 'amazonpayments/general/active';
    const MODULE_CONFIG      = 'Creativestyle_AmazonPayments';

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
        $order->getPayment()->setAdditionalInformation(
            'amazon_order_reference_id',
            $quote->getPayment()
                  ->getTransactionId()
        );

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
            Mage::dispatchEvent(
                'sales_model_service_quote_submit_success',
                array(
                    'order' => $order,
                    'quote' => $quote
                )
            );
        } catch (Exception $e) {
            //reset order ID's on exception, because order not saved
            $order->setId(null);
            /** @var $item Mage_Sales_Model_Order_Item */
            foreach ($order->getItemsCollection() as $item) {
                $item->setOrderId(null);
                $item->setItemId(null);
            }

            Mage::dispatchEvent(
                'sales_model_service_quote_submit_failure',
                array(
                    'order' => $order,
                    'quote' => $quote
                )
            );
            throw $e;
        }
        Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));
        Mage::dispatchEvent('sales_model_service_quote_submit_after', array('order' => $order, 'quote' => $quote));

        return $order;
    }

    /**
     * @param $order            Mage_Sales_Model_Order
     *                          todo: refund & support for mage v1.4.1.1
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        try {
            $orderTrans = Mage::getModel('sales/order_payment_transaction');
            $orderTrans->setOrderPaymentObject($order->getPayment());
            $orderTrans->setIsClosed(false);
            $orderTrans->setTxnId($paymentInfos['mws_order_id']);

            if (Mage::helper('shopgate/config')->getIsMagentoVersionLower15()) {
                $orderTrans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
            } else {
                $orderTrans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
            }

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
     * @param $info     array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $info)
    {
        $payment = Mage::helper('shopgate/import_order')->getMagentoPaymentMethod($this::PAYMENT_IDENTIFIER);;
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
        } else {
            $quote->getShippingAddress()->setPaymentMethod($payment->getCode() ? $payment->getCode() : null);
        }

        $data = array(
            'method' => $payment->getCode(),
            'checks' => Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_COUNTRY
                        | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_CURRENCY
                        | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
        );

        $quote->getPayment()->importData($data);
        $quote->getPayment()->setTransactionId($info['mws_order_id']);
        return $quote;
    }

    /**
     * Set order state if non is set,
     * else ignore as Amazon plugin handles all that
     *
     * @param $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        //backup for potential plugin lower version malfunctions
        if (!$magentoOrder->getState()) {
            $magentoOrder->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                Mage_Sales_Model_Order::STATE_PROCESSING
            );
        }
        $magentoOrder->setShopgateStatusSet(true);
        return $magentoOrder;
    }
}