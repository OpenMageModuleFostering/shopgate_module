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
 * General fallback for all COD methods
 */
class Shopgate_Framework_Model_Payment_Simple_Cod_Abstract 
    extends Shopgate_Framework_Model_Payment_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER     = ShopgateOrder::COD;
    const XML_CONFIG_FEE_LOCAL   = '';
    const XML_CONFIG_FEE_FOREIGN = '';

    /**
     * Run fee processing before everything
     */
    public function setUp()
    {
        $this->processPaymentFee();
    }

    /**
     * No need to pull status, it is assigned automatically,
     * defaults to 'Pending' when not set in config.
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return mixed
     */
    public function setOrderStatus($magentoOrder)
    {
        return $magentoOrder->setData('shopgate_status_set', true);
    }

    /**
     * If the COD config has a payment fee set, overwrite the fee
     * that is coming form Merchant API server
     */
    protected function processPaymentFee()
    {
        $local   = $this->getConstant('XML_CONFIG_FEE_LOCAL');
        $foreign = $this->getConstant('XML_CONFIG_FEE_FOREIGN');
        $sgOrder = $this->getShopgateOrder();

        if (Mage::getStoreConfig($local) || Mage::getStoreConfig($foreign)) {
            $sgOrder->setAmountShopPayment(0);
        }
    }
}