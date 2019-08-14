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
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: Peter Liebig
 * Date: 27.01.14
 * Time: 15:55
 * E-Mail: p.liebig@me.com
 */

/**
 * mobile payment block
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Block_Payment_MobilePayment extends Mage_Payment_Block_Info
{
    /**
     * construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopgate/payment/mobile_payment.phtml');
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getMethod()->getInfoInstance()->getOrder();
    }

    /**
     * @return Shopgate_Framework_Model_Shopgate_Order
     */
    public function getShopgateOrder()
    {
        return Mage::getModel("shopgate/shopgate_order")->load($this->getOrder()->getId(), 'order_id');
    }

    /**
     * @return string
     */
    public function getShopgateOrderNumber()
    {
        return $this->getShopgateOrder()->getShopgateOrderNumber();
    }

    /**
     * @return array
     */
    public function getPaymentInfos()
    {
        $data = array();

        if ($this->getShopgateOrder()->getReceivedData()) {
            $data = unserialize($this->getShopgateOrder()->getReceivedData());
            $data = $data->getPaymentInfos();
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function hasDifferentPrices()
    {
        $order         = $this->getOrder();
        $shopgateOrder = $this->getShopgateOrder()->getShopgateOrderObject();

        $isDifferent = !Mage::helper("shopgate")->isOrderTotalCorrect($shopgateOrder, $order, $msg);

        return $isDifferent;
    }
}
