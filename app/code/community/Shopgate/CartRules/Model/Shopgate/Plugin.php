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
class Shopgate_CartRules_Model_Shopgate_Plugin extends Shopgate_Framework_Model_Shopgate_Plugin
{
    const CART_RULE_COUPON_CODE = '1';
    
    /**
     * Rewrite adaption for cart price rules to work
     * without a coupon code.
     *
     * @see https://shopgate.atlassian.net/browse/MAGENTO-1163
     * @param              $mageCart
     * @param ShopgateCart $cart
     * @return array|null
     */
    public function checkCoupons($mageCart, ShopgateCart $cart)
    {
        /* @var $mageQuote Mage_Sales_Model_Quote */
        /* @var $mageCart Mage_Checkout_Model_Cart */
        /* @var $mageCoupon Mage_SalesRule_Model_Coupon */
        /* @var $mageRule Mage_SalesRule_Model_Rule */
        $mageQuote          = $mageCart->getQuote();
        $mageQuote->setTotalsCollectedFlag(false)->collectTotals();

        $externalCoupons    = array();
        $validCouponsInCart = 0;
        $returnEmptyCoupon  = false;
        $appliedRules       = $mageQuote->getAppliedRuleIds();
        $totals             = $mageQuote->getTotals();
        $discountAmount     = empty($totals['discount']) ? 0 : $totals['discount']->getValue();

        if (!$cart->getExternalCoupons()
            && empty($discountAmount)
        ) {
            return null;
        }

        foreach ($cart->getExternalCoupons() as $coupon) {
            if ($coupon->getCode() === self::CART_RULE_COUPON_CODE) {
                $returnEmptyCoupon = true;
                continue;
            }
            /** @var ShopgateExternalCoupon $coupon */
            $externalCoupon = new ShopgateExternalCoupon();
            $externalCoupon->setIsValid(true);
            $externalCoupon->setCode($coupon->getCode());

            try {
                $mageQuote->setCouponCode($coupon->getCode());
                $mageQuote->setTotalsCollectedFlag(false)->collectTotals();
                $totals = $mageQuote->getTotals();
            } catch (Exception $e) {
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage($e->getMessage());
            }

            if ($this->_getConfigHelper()->getIsMagentoVersionLower1410()) {
                $mageRule   = Mage::getModel('salesrule/rule')->load($coupon->getCode(), 'coupon_code');
                $mageCoupon = $mageRule;
            } else {
                $mageCoupon = Mage::getModel('salesrule/coupon')->load($coupon->getCode(), 'code');
                $mageRule   = Mage::getModel('salesrule/rule')->load($mageCoupon->getRuleId());
            }

            if ($mageRule->getId() && $mageQuote->getCouponCode()) {
                $discountName = isset($totals['discount'])
                    ? $totals['discount']->getTitle()
                    : $mageRule->getDescription();

                $couponInfo              = array();
                $couponInfo["coupon_id"] = $mageCoupon->getId();
                $couponInfo["rule_id"]   = $mageRule->getId();

                $amountCoupon = $mageQuote->getSubtotal() - $mageQuote->getSubtotalWithDiscount();

                $storeLabel = $mageRule->getStoreLabel(Mage::app()->getStore()->getId());
                $externalCoupon->setName($storeLabel ? $storeLabel : $mageRule->getName());
                $externalCoupon->setDescription($discountName);
                $externalCoupon->setIsFreeShipping((bool)$mageQuote->getShippingAddress()->getFreeShipping());
                $externalCoupon->setInternalInfo($this->jsonEncode($couponInfo));
                if ($this->useTaxClasses) {
                    $externalCoupon->setAmountGross($amountCoupon);
                } else {
                    $externalCoupon->setAmountNet($amountCoupon);
                }
                if (!$amountCoupon && !$externalCoupon->getIsFreeShipping()) {
                    $externalCoupon->setIsValid(0);
                    $externalCoupon->setAmount(0);
                    $externalCoupon->setNotValidMessage(
                        $this->_getHelper()->__(
                            'Coupon code "%s" is not valid.',
                            Mage::helper('core')->htmlEscape($coupon->getCode())
                        )
                    );
                }
            } else {
                $externalCoupon->setIsValid(0);
                $externalCoupon->setAmount(0);
                $externalCoupon->setNotValidMessage(
                    $this->_getHelper()->__(
                        'Coupon code "%s" is not valid.',
                        Mage::helper('core')->htmlEscape($coupon->getCode())
                    )
                );
            }

            if ($externalCoupon->getIsValid() && $validCouponsInCart >= 1) {
                $errorCode = ShopgateLibraryException::COUPON_TOO_MANY_COUPONS;
                $externalCoupon->setIsValid(false);
                $externalCoupon->setNotValidMessage(ShopgateLibraryException::getMessageFor($errorCode));
            }

            if ($externalCoupon->getIsValid()) {
                $validCouponsInCart++;
            }
            $externalCoupon->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
            $externalCoupons[] = $externalCoupon;
        }

        if (!empty($discountAmount)
            && $validCouponsInCart == 0
        ) {
            try {
                $totals = $mageQuote->getTotals();
                if ($totals['discount']) {
                    $discount = $totals['discount'];

                    /** @var ShopgateExternalCoupon $coupon */
                    $coupon = new ShopgateExternalCoupon();
                    $coupon->setIsValid(true);
                    $coupon->setCode(self::CART_RULE_COUPON_CODE);
                    $title = $discount->getTitle();
                    $title = empty($title) ? Mage::helper('sales')->__('Discount') : $title;
                    $coupon->setName($title);
                    $coupon->setDescription($discount->getTitle());
                    $amountCoupon = abs($discount->getValue());
                    if ($this->useTaxClasses) {
                        $coupon->setAmountGross($amountCoupon);
                    } else {
                        $coupon->setAmountNet($amountCoupon);
                    }
                    $coupon->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
                    $coupon->setInternalInfo(
                        $this->_getHelper()->getConfig()->jsonEncode(array('rule_ids' => $appliedRules))
                    );
                    $externalCoupons[] = $coupon;
                    $returnEmptyCoupon = false;
                }
            } catch (Exception $e) {
                $this->log(
                    "Could not add rule with id " . $appliedRules . " to quote",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
                return null;
            }
        }

        if ($returnEmptyCoupon) {
            $coupon = new ShopgateExternalCoupon();
            $coupon->setCode(self::CART_RULE_COUPON_CODE);
            $coupon->setName(Mage::helper('sales')->__('Discount'));
            $coupon->setIsValid(false);
            $externalCoupons[] = $coupon;
        }

        return $externalCoupons;
    }

    /**
     * Add coupon from this system to quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateCartBase       $order
     *
     * @return Mage_Sales_Model_Quote
     * @throws ShopgateLibraryException
     */
    protected function _setQuoteShopCoupons($quote, $order)
    {
        $externalCoupons = array();

        foreach ($order->getExternalCoupons() as $coupon) {
            /* @var $coupon ShopgateExternalCoupon */
            if ($coupon->getCode() !== self::CART_RULE_COUPON_CODE ) {
                $externalCoupons[] = $coupon;
            }
        }
        $order->setExternalCoupons($externalCoupons);
        $quote = parent::_setQuoteShopCoupons($quote, $order);

        $session = Mage::getSingleton('checkout/session');
        $session->replaceQuote($quote);
        return $quote;
    }
}