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
class Shopgate_Framework_Helper_Shipping
{
    /**
     * Retrieves an object providing the rates method,
     * title and whether it was mapped. Need to collect
     * in order to get the correct rates.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateOrder          $order
     *
     * @return Varien_Object - data('title', 'method')
     * @throws Exception
     */
    public function getInfo(Mage_Sales_Model_Quote $quote, ShopgateOrder $order)
    {
        $result = new Varien_Object();
        $result->setData('method', 'shopgate_fix');
        $result->setData('title', $order->getShippingInfos()->getDisplayName());

        $quote->setData('inventory_processed_flag', false);
        $quote->setData('totals_collected_flag', false);

        /**
         * Needs to collect rates else it will fail on ->submitAll() call
         */
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
        $rates = $quote->getShippingAddress()->collectShippingRates()->getGroupedAllShippingRates();

        if ($this->hasFreeShippingCoupon($order)) {
            return $result;
        }

        if (array_key_exists('shopgate', $rates)) {
            /** @var Mage_Sales_Model_Quote_Address_Rate $addressRate */
            $addressRate = $rates['shopgate'][0];

            foreach ($rates as $_key) {
                foreach ($_key as $rate) {
                    /** @var Mage_Sales_Model_Quote_Address_Rate $rate */
                    if ($rate->getCode() == $addressRate->getMethodTitle()) {
                        $result->setData('method', $addressRate->getMethodTitle());
                        $addressRate->setCarrierTitle($rate->getCarrierTitle());
                        $addressRate->setMethodTitle($rate->getMethodTitle());
                        $addressRate->save();
                        $result->setData(
                            'title', $addressRate->getCarrierTitle() . " - " . $addressRate->getMethodTitle()
                        );
                        $result->setData('mapped', true);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Processes rates, applies new shipping info and re-collects
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param ShopgateOrder          $order
     *
     * @return Varien_Object
     */
    public function processQuote(Mage_Sales_Model_Quote $quote, ShopgateOrder $order)
    {
        $shipping = $this->getInfo($quote, $order);

        if (!$shipping->getData('mapped')) {
            $quote->getShippingAddress()->setShippingMethod($shipping->getData('method'));
        }

        $quote->getShippingAddress()->setShippingDescription($shipping->getData('title'));

        /**
         * Need to reprocess to apply shopgate_fix fee of $0 to totals AND there is an
         * issue with two collector calls reducing taxes for coupons on lower mage versions
         *
         * @see https://shopgate.atlassian.net/browse/MAGENTO-880
         */
        if ($this->hasFreeShippingCoupon($order) && !Mage::helper('shopgate/config')->getIsMagentoVersionLower15()) {
            $quote->setData('totals_collected_flag', false);
            $quote->collectTotals();
        }

        $quote->save();

        return $shipping;
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return bool
     */
    private function shopgateCouponExists(ShopgateOrder $order)
    {
        foreach ($order->getItems() as $item) {
            if ($item->isSgCoupon()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if order has a Free Ship coupon
     * applied to it
     *
     * @param ShopgateOrder $order
     *
     * @return bool
     */
    public function hasFreeShippingCoupon(ShopgateOrder $order)
    {
        return $this->shopgateCouponExists($order) && $order->getAmountShipping() == 0;
    }
}