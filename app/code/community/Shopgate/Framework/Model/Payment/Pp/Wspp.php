<?php
/**
 * User: pliebig
 * Date: 10.09.14
 * Time: 10:05
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * Class to manipulate the order payment data with amazon payment data
 *
 * @package Shopgate_Framework_Model_Payment_Wspp
 * @author  Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 * @author  Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Pp_Wspp
    extends Shopgate_Framework_Model_Payment_Pp_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::PP_WSPP_CC;
    const MODULE_CONFIG      = 'Mage_Paypal';
    const PAYMENT_MODEL      = 'paypal/direct';
    const XML_CONFIG_ENABLED = 'payment/paypal_wps_express/active';

    /**
     * Create new order for amazon payment
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
     *                          // TODO Refund
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentInfos  = $this->getShopgateOrder()->getPaymentInfos();
        $paypalIpnData = json_decode($paymentInfos['paypal_ipn_data'], true);
        $paypalIpnData = array_merge($paymentInfos['credit_card'], $paypalIpnData);
        $paymentStatus = $this->_getPaymentHelper()->filterPaymentStatus($paypalIpnData['payment_status']);

        $trans = Mage::getModel('sales/order_payment_transaction');
        $trans->setOrderPaymentObject($order->getPayment());
        $trans->setTxnId($paypalIpnData['txn_id']);
        $trans->setIsClosed(false);

        try {
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($order);
            switch ($paymentStatus) {
                // paid
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                    if ($order->getPayment()->getIsTransactionPending()) {
                        $invoice->setIsPaid(false);
                    } else { // normal online capture: invoice is marked as "paid"
                        $invoice->setIsPaid(true);
                        $invoice->pay();
                    }
                    break;
                // refund by merchant on PayPal side
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDED:
                    //$this->_getPaymentHelper()->registerPaymentRefund($additionalData, $order);
                    break;
                // payment was obtained, but money were not captured yet
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                    foreach ($paypalIpnData as $key => $value) {
                        if (strpos($key, 'fraud_management_pending_filters_') !== false) {
                            $order->getPayment()->setIsTransactionPending(true);
                            $order->getPayment()->setIsFraudDetected(true);
                        }
                    }

                    $trans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    $invoice->setIsPaid(false);
                    $order->getPayment()->setAmountAuthorized($order->getTotalDue());
                    break;
                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
            $trans->save();
            $invoice->setTransactionId($paypalIpnData['txn_id']);
            $invoice->save();
            $order->addRelatedObject($invoice);
            $this->_getPaymentHelper()->importPaymentInformation($order->getPayment(), $paypalIpnData);
            $order->getPayment()->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $paypalIpnData
            );
            $order->getPayment()->setCcOwner($paypalIpnData['holder']);
            $order->getPayment()->setCcType($paypalIpnData['type']);
            $order->getPayment()->setCcNumberEnc($paypalIpnData['masked_number']);
            $order->getPayment()->setLastTransId($paypalIpnData['txn_id']);
        } catch (Exception $x) {
            $comment = $this->_getPaymentHelper()->createIpnComment(
                $order,
                Mage::helper('paypal')->__('Note: %s', $x->getMessage()),
                true
            );
            $comment->save();
            Mage::logException($x);
        }
        return $order;
    }

    /**
     * @param $quote            Mage_Sales_Model_Quote
     * @param $data             array
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $data)
    {
        $ipnData = json_decode($data['paypal_ipn_data'], true);
        $this->_getPaymentHelper()->importToPayment(
            $ipnData,
            $quote->getPayment()->getMethodInstance()->getInfoInstance()
        );
        $quote->getPayment()->setTransactionId($data['paypal_txn_id']);
        $quote->getPayment()->setCcOwner($data['credit_card']['holder']);
        $quote->getPayment()->setCcType($data['credit_card']['type']);
        $quote->getPayment()->setCcNumberEnc($data['credit_card']['masked_number']);
        $quote->setData('paypal_ipn_data', $data['paypal_ipn_data']);
        $quote->getPayment()->setLastTransId($data['paypal_txn_id']);
        return $quote;
    }

    /**
     * Set order status
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function setOrderStatus($magentoOrder)
    {
        return $this->orderStatusManager($magentoOrder);
    }

    /**
     * Rewrite to tailor for lower versions
     * of magento's PP implementation
     *
     * @return bool
     */
    public function isEnabled()
    {
        return parent::isEnabled() || $this->_isOldWsppEnabled();
    }

    /**
     * Checks if Website Payments Pro OR
     * Website Payment Pro Payflow are enabled
     *
     * @return bool
     */
    private function _isOldWsppEnabled()
    {
        $wpp    = Mage::getStoreConfig('payment/paypal_direct/active');
        $wppp   = Mage::getStoreConfig('payment/paypaluk_direct/active');
        $result = !empty($wpp) || !empty($wppp);
        
        if (!$result) {
            $debug = $this->_getHelper()->__('Neither WSPP or WSPP Payflow are enabled');
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }
        return $result;
    }

    /**
     * @return Shopgate_Framework_Helper_Payment_Wspp
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_wspp');
    }
}