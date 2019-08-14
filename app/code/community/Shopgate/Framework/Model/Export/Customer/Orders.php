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
 * User: pliebig
 * Date: 14.08.14
 * Time: 18:41
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * Model to export get_orders for customers
 *
 * @package     Shopgate_Framework
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Export_Customer_Orders extends Shopgate_Framework_Model_Export_Abstract
{
    /**
     * getting orders for the customer filtered by given data 
     * 
     * @param $customerToken
     * @param $limit
     * @param $offset
     * @param $orderDateFrom
     * @param $sortOrder
     * @return array
     * @throws ShopgateLibraryException
     */
    public function getOrders($customerToken, $limit, $offset, $orderDateFrom, $sortOrder)
    {
        $relation = Mage::getModel('shopgate/customer')->loadByToken($customerToken);
        $response = array();
        if ($relation->getId()) {

            $sort    = str_replace('created_', '', $sortOrder);
            $_orders = Mage::getModel('sales/order')->getCollection()->addFieldToSelect('*');
            if ($orderDateFrom) {
                $_orders->addFieldToFilter(
                        'created_at',
                        array(
                            'from' => date('Y-m-d H:i:s', strtotime($orderDateFrom))
                        )
                );
            }
            $_orders->addFieldToFilter('customer_id', $relation->getCustomerId())
                    ->addFieldToFilter(
                    'state',
                    array(
                        'in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()
                    )
                )->setOrder('created_at', $sort);
            $_orders->getSelect()->limit($limit, $offset);
            if ($_orders->count() > 0) {
                /** @var Mage_Sales_Model_Order $order */
                foreach ($_orders as $order) {
                    /** @var Shopgate_Framework_Model_Shopgate_Order $shopgateOrder */
                    $shopgateOrder = $this->_getShopgateOrderNumber($order->getId());

                    $shopgateExternalOrder = new ShopgateExternalOrder();
                    $shopgateExternalOrder->setOrderNumber(($shopgateOrder) ? $shopgateOrder->getShopgateOrderNumber() : null);
                    $shopgateExternalOrder->setExternalOrderId($order->getId());
                    $shopgateExternalOrder->setExternalOrderNumber($order->getIncrementId());
                    $shopgateExternalOrder->setCreatedTime(date(DateTime::ISO8601, strtotime($order->getCreatedAt())));
                    $shopgateExternalOrder->setMail($order->getCustomerEmail());
                    $shopgateExternalOrder->setCurrency($order->getOrderCurrencyCode());
                    $shopgateExternalOrder->setPaymentMethod($order->getPayment()->getMethodInstance()->getTitle());
                    $shopgateExternalOrder->setIsPaid(($shopgateOrder) ? $shopgateOrder->getIsPaid() : null);
                    $shopgateExternalOrder->setPaymentTransactionNumber($this->_getPaymentTransactionNumber($order));
                    $shopgateExternalOrder->setAmountComplete($order->getGrandTotal());
                    $shopgateExternalOrder->setInvoiceAddress($this->_getShopgateAddressFromOrderAddress($order->getBillingAddress()));
                    $shopgateExternalOrder->setDeliveryAddress($this->_getShopgateAddressFromOrderAddress($order->getShippingAddress()));
                    $shopgateExternalOrder->setItems($this->_getOrderItemsFormatted($order));
                    $shopgateExternalOrder->setOrderTaxes($this->_getOrderTaxFormatted($order));
                    $shopgateExternalOrder->setDeliveryNotes($this->_getDeliveryNotes($order));
                    $shopgateExternalOrder->setExternalCoupons($this->_getCouponsFormatted($order));

                    array_push($response, $shopgateExternalOrder);
                }
            }
            return $response;
        } else {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_TOKEN_INVALID);
        }
    }

    /**
     * @param $orderId
     * @return bool|string
     */
    protected function _getShopgateOrderNumber($orderId)
    {
        /** @var Shopgate_Framework_Model_Shopgate_Order $shopgateOrder */
        $shopgateOrder = Mage::getModel("shopgate/shopgate_order")->load($orderId, "order_id");
        if ($shopgateOrder->getId()) {
            return $shopgateOrder;
        } else {
            return false;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return null|string
     */
    protected function _getPaymentTransactionNumber($order)
    {
        $transactionNumber = null;
        if ($order->getExtOrderId()) {
            return $order->getExtOrderId();
        }
        if ($order->getPayment()->getLastTransId()) {
            return $order->getPayment()->getLastTransId();
        }
        if ($order->getPayment()->getCcTransId()) {
            return $order->getPayment()->getCcTransId();
        }
        return $transactionNumber;
    }

    /**
     * @param Mage_Sales_Model_Order_Address $address
     * @return array
     */
    protected function _getShopgateAddressFromOrderAddress($address)
    {
        $shopgateAddress = new ShopgateAddress();
        $shopgateAddress->setFirstName($address->getFirstname());
        $shopgateAddress->setLastName($address->getLastname());
        $shopgateAddress->setGender(
                        $this->_getCustomerHelper()->getShopgateCustomerGender($address)
        );
        $shopgateAddress->setCompany($address->getCompany());
        $shopgateAddress->setPhone($address->getTelephone());
        $shopgateAddress->setStreet1($address->getStreet1());
        $shopgateAddress->setStreet2($address->getStreet2());
        $shopgateAddress->setCity($address->getCity());
        $shopgateAddress->setZipcode($address->getPostcode());
        $shopgateAddress->setCountry($address->getCountry());
        $shopgateAddress->setState($this->_getHelper()->getIsoStateByMagentoRegion($address));

        return $shopgateAddress->toArray();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderItemsFormatted($order)
    {
        $items = array();
        foreach ($order->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            // avoid child and parent products in list 
            if (!$item->getParentItemId()) {
                $shopgateItem = new ShopgateExternalOrderItem();
                $shopgateItem->setItemNumber($item->getProductId());
                $shopgateItem->setItemNumberPublic($item->getSku());
                $shopgateItem->setQuantity((int)$item->getQtyOrdered());
                $shopgateItem->setname($item->getName());
                $shopgateItem->setUnitAmount($item->getRowTotal());
                $shopgateItem->setUnitAmountWithTax($item->getRowTotalInclTax());
                $shopgateItem->setTaxPercent($item->getTaxPercent());
                $shopgateItem->setCurrency($order->getOrderCurrencyCode());
                $shopgateItem->setDescription($item->getDescription());
                array_push($items, $shopgateItem);
            }
        }

        return $items;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getOrderTaxFormatted($order)
    {
        $taxObjects = array();
        $info       = $order->getFullTaxInfo();
        if (!empty($info)) {
            foreach ($info as $_tax) {
                $tax = new ShopgateExternalOrderTax();
                $tax->setAmount($_tax['amount']);
                $tax->setLabel($_tax['rates'][0]['title']);
                $tax->setTaxPercent((float)$_tax['percent']);
                array_push($taxObjects, $tax);
            }
        }
        return $taxObjects;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getDeliveryNotes($order)
    {
        $deliveryNotes = array();
        foreach ($order->getShipmentsCollection() as $shipment) {
            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            foreach ($shipment->getAllTracks() as $track) {
                /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                $note = new ShopgateDeliveryNote();
                $note->setShippingServiceId($track->getTitle());
                $note->setTrackingNumber($track->getNumber());
                $note->setShippingTime($track->getCreatedAt());
                array_push($deliveryNotes, $note);
            }
        }
        return $deliveryNotes;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function _getCouponsFormatted($order)
    {
        $result = array();
        if ($order->getCouponCode()) {
            if (Mage::helper("shopgate/config")->getIsMagentoVersionLower1410()) {
                $mageRule   = Mage::getModel('salesrule/rule')->load($order->getCouponCode(), 'coupon_code');
                $mageCoupon = $mageRule;
            } else {
                $mageCoupon = Mage::getModel('salesrule/coupon')->load($order->getCouponCode(), 'code');
                $mageRule   = Mage::getModel('salesrule/rule')->load($mageCoupon->getRuleId());
            }

            $externalCoupon          = new ShopgateExternalCoupon();
            $couponInfo              = array();
            $couponInfo["coupon_id"] = $mageCoupon->getId();
            $couponInfo["rule_id"]   = $mageRule->getId();

            $externalCoupon->setCode($order->getCouponCode());
            $externalCoupon->setCurrency($order->getOrderCurrencyCode());
            $externalCoupon->setName($mageRule->getName());
            $externalCoupon->setDescription($mageRule->getDescription());
            $externalCoupon->setInternalInfo($this->_getConfig()->jsonEncode($couponInfo));
            $externalCoupon->setAmount($order->getDiscountAmount());
            array_push($result, $externalCoupon);
        }

        return $result;
    }
}