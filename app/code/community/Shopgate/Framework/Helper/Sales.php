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
 * @author      Shopgate GmbH <interfaces@shopgate.com>
 * @description Sales helper for everything related to sales / orders / quote and stuff
 */
class Shopgate_Framework_Helper_Sales extends Mage_Core_Helper_Abstract
{
    /**
     * @param ShopgateCartBase $order
     * @param ShopgateAddress  $address
     * @param bool             $saveInAddressBook
     * @return array
     */
    public function createAddressData($order, $address, $saveInAddressBook = false)
    {
        ShopgateLogger::getInstance()->log('_createAddressData', ShopgateLogger::LOGTYPE_DEBUG);

        $phoneNumber = $order->getMobile();
        if (empty($phoneNumber)) {
            $phoneNumber = $order->getPhone();
        }
        if (empty($phoneNumber)) {
            $phoneNumber = 'n.a.';
        }

        $region = Mage::helper('shopgate/customer')->getMagentoRegionByShopgateAddress($address);

        $prefix = 'Mr.';
        if ($address->getGender() == 'f') {
            $prefix = 'Mrs.';
        }

        $addressData = array(
            'prefix'               => $this->_getHelper()->__($prefix),
            'company'              => $address->getCompany(),
            'firstname'            => $address->getFirstName(),
            'lastname'             => $address->getLastName(),
            'street'               => $address->getStreet1()
                                      . ($address->getStreet2() ? '\n' . $address->getStreet2() : ''),
            'city'                 => $address->getCity(),
            'postcode'             => $address->getZipcode(),
            'telephone'            => $phoneNumber,
            'email'                => $order->getMail(),
            'country_id'           => $address->getCountry(),
            'region_id'            => $region->getId(),
            'save_in_address_book' => $saveInAddressBook,
        );

        $customFields = array();
        foreach ($address->getCustomFields() as $field) {
            $customFields[] = array($field->getInternalFieldName() => $field->getValue());
        }
        $addressData = array_merge($addressData, $customFields);

        return $addressData;
    }

    /**
     * Returns all valid payment methods base on current quote.
     * (check address, order total amount etc.)
     *
     * @param Mage_Checkout_Model_Cart $mageCart
     * @return array
     */
    public function getPaymentMethods($mageCart)
    {
        /** @var Mage_Sales_Model_quote $quote */
        $quote          = $mageCart->getQuote();
        $methods        = array();
        $paymentMethods = Mage::helper('payment')->getStoreMethods(Mage::app()->getStore()->getId(), $quote);

        /** @var Mage_Payment_Model_Method_Abstract $_paymentMethod */
        foreach ($paymentMethods as $_paymentMethod) {

            if (!$_paymentMethod->canUseForCountry($quote->getBillingAddress()->getCountry())
                || !$_paymentMethod->canUseForCurrency($quote->getStore()->getBaseCurrencyCode())
            ) {
                continue;
            }

            /**
             * Checking for min/max order total for assigned payment method
             */
            $total    = $quote->getBaseGrandTotal();
            $minTotal = $_paymentMethod->getConfigData('min_order_total');
            $maxTotal = $_paymentMethod->getConfigData('max_order_total');

            if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
                continue;
            }
            $method = new ShopgatePaymentMethod();
            $method->setId($_paymentMethod->getCode());
            $method->setAmount(0.00);
            $method->setAmountWithTax(0.00);
            $method->setTaxClass('');
            $method->setTaxPercent(0.00);

            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Returns all valid shipping methods for current quote.
     * (based on provided delivery address)
     *
     * @param Mage_Checkout_Model_Cart $mageCart
     * @return array
     */
    public function getShippingMethods($mageCart)
    {
        /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress    = $mageCart->getQuote()->getShippingAddress();
        $billingAddress     = $mageCart->getQuote()->getBillingAddress();
        $customerTaxClass   = $mageCart->getQuote()->getCustomer()->getTaxClassId();
        $storeViewId        = Mage::helper('shopgate/config')->getConfig()->getStoreViewId();
        $store              = Mage::app()->getStore($storeViewId);
        $calc               = Mage::getSingleton('tax/calculation');
        $rateRequest        = $calc->getRateRequest($shippingAddress, $billingAddress, $customerTaxClass, $store);
        $rates              = $calc->getRatesForAllProductTaxClasses($rateRequest);
        $taxClassIdShipping = Mage::helper('tax')->getShippingTaxClass($storeViewId);
        $taxRateShipping    = $taxClassIdShipping ? $rates[$taxClassIdShipping] : 0;

        $shippingAddress->collectTotals();
        $shippingAddress->collectShippingRates();


        $methods = array();
        /** @var Mage_Sales_Model_Quote_Address_Rate $_rate */
        foreach ($shippingAddress->getShippingRatesCollection() as $_rate) {
            if ($_rate instanceof Mage_Shipping_Model_Rate_Result_Error
                || strpos($_rate->getCode(), 'error') !== false
                || $_rate->getCarrierInstance() == false
            ) {
                /* skip errors so they dont get processed as valid shipping rates without any cost */
                ShopgateLogger::getInstance()->log(
                    "Skipping Shipping Rate because of Error Type: '" . $_rate->getCode() . "'",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
                continue;
            }

            $shippingAddress->setShippingMethod($_rate->getCode());
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectTotals();

            $method = new ShopgateShippingMethod();
            $method->setId($_rate->getCode());
            $method->setTitle($_rate->getMethodTitle());
            $method->setShippingGroup($_rate->getCarrier());
            $method->setDescription($_rate->getMethodDescription() ? $_rate->getMethodDescription() : '');
            $method->setSortOrder($_rate->getCarrierInstance()->getSortOrder());
            $method->setAmount($shippingAddress->getBaseShippingAmount());
            $method->setAmountWithTax($shippingAddress->getBaseShippingInclTax());
            $method->setTaxClass($taxClassIdShipping);
            $method->setTaxPercent(number_format($taxRateShipping, 2));

            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Returns all items from quote and validates
     * them by quantity and addresses.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCart           $cart
     *
     * @return array
     */
    public function getItems($quote, ShopgateCart $cart)
    {
        $validator = Mage::getModel('shopgate/shopgate_cart_validation_stock');
        $items     = array();
        $quote->collectTotals();

        /** @var Mage_Sales_Model_Quote_Item $_item */
        foreach ($quote->getAllVisibleItems() as $_item) {
            $price            = $_item->getProduct()->getFinalPrice();
            $priceIncludesTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
            $percent          = $_item->getTaxPercent();
            if ($priceIncludesTax) {
                $priceInclTax = $price;
                $priceExclTax = $price / (1 + ($percent / 100));
            } else {
                $priceInclTax = $price * (1 + ($percent / 100));
                $priceExclTax = $price;
            }
            $items[] = $validator->validateStock($_item, $priceInclTax, $priceExclTax);
        }

        if (count($cart->getItems()) !== $quote->getItemsCollection()->getSize()) {
            foreach ($cart->getItems() as $orderItem) {
                $info = Zend_Json::decode($orderItem->getInternalOrderInfo());
                if (!empty($info['error_message'])) {
                    $errorCode = $this->_translateMageError($info['error_message']);
                    $items[]   = $this->_getHelper()->getCartItemFromOrderItem($orderItem, $errorCode);
                }
            }
        }

        return $items;
    }

    /**
     * Translates the magento error into an exportable check_cart Shopgate error code
     *
     * @param string $error - magento error message
     * @return int - Shopgate error code
     */
    private function _translateMageError($error)
    {
        $sgError = ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK;
        if ($error == Mage::helper('catalog')->__('The text is too long')
            || $error == Mage::helper('catalog')->__('Please specify the product required option(s).')
            || $error == Mage::helper('catalog')->__('Please specify the product\'s option(s).')
        ) {
            $sgError = ShopgateLibraryException::CART_ITEM_INPUT_VALIDATION_FAILED;
        }
        return $sgError;
    }

    /**
     * Fetches any related customer_group to the given cart object
     *
     * @param ShopgateCart $cart
     * @param int          $websiteId
     *
     * @return array
     */
    protected function _getCustomerGroups(ShopgateCart $cart, $websiteId)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer           = Mage::getModel('customer/customer');
        $externalCustomerId = $cart->getExternalCustomerId();

        if ($externalCustomerId) {
            $customer->load($externalCustomerId);
        } else {
            if ($cart->getDeliveryAddress() && $cart->getDeliveryAddress()->getMail()) {
                $customer->setWebsiteId($websiteId)->loadByEmail($cart->getDeliveryAddress()->getMail());
            } else {
                if ($cart->getInvoiceAddress() && $cart->getInvoiceAddress()->getMail()) {
                    $customer->setWebsiteId($websiteId)->loadByEmail($cart->getInvoiceAddress()->getMail());
                }
            }
        }
        if (!$externalCustomerId && $customer->getId()) {
            $cart->setExternalCustomerId($customer->getId());
        }

        return Mage::helper('shopgate/customer')->getShopgateCustomerGroups($customer);
    }

    /**
     * Fetches any related customer_data to the given cart object
     *
     * @param ShopgateCart $cart
     * @param int          $storeViewId
     *
     * @return array
     */
    public function getCustomerData(ShopgateCart $cart, $storeViewId)
    {
        $websiteId      = Mage::getModel('core/store')->load($storeViewId)->getWebsite()->getId();
        $customerGroups = $this->_getCustomerGroups($cart, $websiteId);
        $result         = array();

        if ($customerGroups) {
            foreach ($customerGroups as $customerGroup) {
                $result['customer_groups'][] = new ShopgateCartCustomerGroup($customerGroup);
            }
        }

        if (isset($result['customer_groups']) && count($result['customer_groups'])) {
            $customerGroupId = $result['customer_groups'][0]->getId();
            $taxClassId      = Mage::getModel('customer/group')->load($customerGroupId)->getTaxClassId();
            $taxClassModel   = Mage::getModel('tax/class')->load($taxClassId);

            $result['customer_tax_class_key'] = $taxClassModel->getId() ? $taxClassModel->getClassName() : null;
        }

        return new ShopgateCartCustomer($result);
    }

    /**
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }
}
