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
class Shopgate_Framework_Model_Modules_Affiliate_Factory extends Shopgate_Framework_Model_Modules_Factory
{
    /**
     * Runs the initial setup functionality. Usually setting
     * up parameters before the totals collector runs.
     *
     * @param Mage_Sales_Model_Quote | null $quote
     *
     * @return bool
     */
    public function setUp($quote = null)
    {
        $result = false;
        if (!$this->getRouter()->getValidator()->isValid()) {
            return $result;
        }

        $utility = $this->getRouter()->getUtility();
        if ($utility instanceof Shopgate_Framework_Model_Modules_Affiliate_Utility) {
            $utility->salesRuleHook();
            $message = 'Ran the sales rule hook in ' . get_class($utility);
            ShopgateLogger::getInstance()->log($message, ShopgateLogger::LOGTYPE_DEBUG);
        }

        $redeemer = $this->getRouter()->getRedeemer();
        if ($redeemer) {
            $result = $redeemer->setAffiliateData(
                $this->getRouter()->getAffiliateParameter(),
                $this->getSgOrder()->getExternalCustomerId(),
                $quote
            );
        }

        return $result;
    }

    /**
     * Retrieves a Shopgate coupon to export in check_cart call
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param bool                   $useTaxClasses
     *
     * @return false | ShopgateExternalCoupon
     */
    public function redeemCoupon(Mage_Sales_Model_Quote $quote, $useTaxClasses)
    {
        if (!$this->getRouter()->getValidator()->isValid()) {
            return false;
        }

        $redeemer  = $this->getRouter()->getRedeemer();
        $parameter = $this->getRouter()->getAffiliateParameter();

        return $redeemer ? $redeemer->retrieveCoupon($quote, $parameter, $useTaxClasses) : false;
    }

    /**
     * Trigger affiliate commission retrieval
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool | Mage_Sales_Model_Quote
     */
    public function promptCommission(Mage_Sales_Model_Order $order)
    {
        if (!$this->getRouter()->getValidator()->isValid()) {
            return false;
        }

        return $this->getRouter()->getRedeemer()->promptCommission($order, $this->getSgOrder());
    }

    /**
     * @see Shopgate_Framework_Model_Modules_Affiliate_Router::getAffiliateParameter
     *
     * @return array | false
     */
    public function getModuleTrackingParameter()
    {
        if (!$this->getRouter()->getValidator()->isValid()) {
            return false;
        }

        return $this->getRouter()->getAffiliateParameter();
    }

    /**
     * Destroys cookies
     */
    public function destroyCookies()
    {
        if ($this->getRouter()->getValidator()->isValid()) {
            $this->getRouter()->getRedeemer()->destroyCookies();
        }
    }
}