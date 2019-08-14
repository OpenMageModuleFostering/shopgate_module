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
class Shopgate_Framework_Model_Modules_Affiliate_Packages_Magestore_Redeem
{
    /**
     * Sets up affiliate data to be pulled in totals collector
     *
     * @param array                         $parameter  - affiliate parameter ['key' => '', 'value' => '']
     * @param string                        $customerId - magento customer id
     * @param Mage_Sales_Model_Quote | null $quote      - manipulate sessions for program initialization
     *
     * @return bool
     */
    public function setAffiliateData($parameter, $customerId, $quote)
    {
        /** @see Magestore_Affiliateplusprogram_Helper_Data::initProgram */
        if ($quote instanceof Mage_Sales_Model_Quote) {
            Mage::getSingleton('checkout/session')->replaceQuote($quote);
            Mage::getSingleton('checkout/cart')->setQuote($quote);
        }

        Mage::getSingleton('customer/session')->setCustomerId($customerId);
        $accountCode = !empty($parameter['value']) ? $parameter['value'] : '';
        $account     = Mage::getSingleton('affiliateplus/session')->getAccount();

        if ($accountCode && $account->getIdentifyCode() != $accountCode) {
            $affiliateAccount = Mage::getModel('affiliateplus/account')->loadByIdentifyCode($accountCode);
            $cookieName       = 'affiliateplus_account_code_';

            if ($affiliateAccount->getId()) {
                $cookie       = Mage::getSingleton('core/cookie');
                $currentIndex = $cookie->get('affiliateplus_map_index');

                for ($i = intval($currentIndex); $i > 0; $i--) {
                    if ($_COOKIE[$cookieName . $i] == $accountCode) {
                        $curI = intval($currentIndex);

                        for ($j = $i; $j < $curI; $j++) {
                            $cookieValue               = $cookie->get($cookieName . intval($j + 1));
                            $_COOKIE[$cookieName . $j] = $cookieValue;
                        }
                        $_COOKIE[$cookieName . $curI] = $accountCode;

                        return true;
                    }
                }
                $currentIndex = $currentIndex ? intval($currentIndex) + 1 : 1;
                /**
                 * todo-sg: may need to be revisited, but the setting & deleting cookies doesn't work
                 * Would this somehow interfere with frontend cookies?
                 * $cookie->set('affiliateplus_map_index', $currentIndex);
                 * $cookie->set("affiliateplus_account_code_$currentIndex", $accountCode);
                 */
                $_COOKIE['affiliateplus_map_index']   = $currentIndex;
                $_COOKIE[$cookieName . $currentIndex] = $accountCode;

                return true;
            }
        }

        return false;
    }

    /**
     * Returns an export ready affiliate coupon, in case there is no
     * affiliate discount it returns false
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $parameter - ('key' => 'get_key', 'value => 'get_val')
     * @param bool                   $useTaxClasses
     *
     * @return false | ShopgateExternalCoupon
     */
    public function retrieveCoupon(Mage_Sales_Model_Quote $quote, $parameter, $useTaxClasses)
    {
        $coupon = false;
        if ($quote->getData('affiliateplus_discount')) {
            $discount = abs($quote->getData('affiliateplus_discount'));
            $coupon   = new ShopgateExternalCoupon();
            $coupon->setIsValid(true);
            $coupon->setCode(Shopgate_Framework_Model_Modules_Affiliate_Utility::COUPON_TYPE);
            $coupon->setName('Affiliate Discount');
            $coupon->setIsFreeShipping(false);
            if ($useTaxClasses) {
                $coupon->setAmountGross($discount);
            } else {
                $coupon->setAmountNet($discount);
            }
            $coupon->setInternalInfo(
                Zend_Json::encode(
                    array(
                        'parameter' => array($parameter['key'], $parameter['value'])
                    )
                )
            );
        } else {
            $message = 'Affiliate discount was not found in the quote';
            ShopgateLogger::getInstance()->log($message, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $coupon;
    }

    /**
     * Prompts to create a commission transaction in case
     * checkout_submit_all_after observer is not triggered
     * by our order import (some payment methods).
     * Note! that there are other observers in Magestore
     * Affiliate that could be triggered to create a
     * transaction.
     *
     * @param Mage_Sales_Model_Order $order
     * @param ShopgateOrder          $sgOrder
     *
     * @return Mage_Sales_Model_Quote
     * @throws Zend_Json_Exception
     */
    public function promptCommission(Mage_Sales_Model_Order $order, ShopgateOrder $sgOrder)
    {
        $customer = Mage::getModel('customer/customer');
        $customer->setData('website_id', Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($sgOrder->getMail());
        $order->setCustomerId($customer->getId());

        $observer = new Varien_Event_Observer();
        $observer->setData('orders', array($order));
        Mage::getModel('affiliateplus/observer')->checkout_submit_all_after($observer);

        return $order;
    }

    /**
     * Destroy cookies when done
     */
    public function destroyCookies()
    {
        $cookie       = Mage::getSingleton('core/cookie');
        $currentIndex = $cookie->get('affiliateplus_map_index');
        $cookieName   = 'affiliateplus_account_code_';

        for ($i = intval($currentIndex); $i > 0; $i--) {
            $curI = intval($currentIndex);

            for ($j = $i; $j < $curI; $j++) {
                $cookieValue               = $cookie->get(
                    $cookieName . intval($j + 1)
                );
                $_COOKIE[$cookieName . $j] = $cookieValue;
            }
            unset($_COOKIE[$cookieName . $currentIndex]);
        }
        $currentIndex = $currentIndex ? intval($currentIndex) + 1 : 1;
        unset($_COOKIE['affiliateplus_map_index']);
        unset($_COOKIE[$cookieName . $currentIndex]);
    }
}