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
 * User: Peter Liebig
 * Date: 23.01.14
 * Time: 00:51
 * E-Mail: p.liebig@me.com
 */

/**
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */

/**
 * @method int getShopgateOrderId()
 * @method Shopgate_Framework_Model_Shopgate_Order setStoreId(int $value)
 * @method int getStoreId()
 * @method int getOrderId()
 * @method Shopgate_Framework_Model_Shopgate_Order setOrderId(string $value)
 * @method string getShopgateOrderNumber()
 * @method Shopgate_Framework_Model_Shopgate_Order setShopgateOrderNumber(string $value)
 * @method bool getIsShippingBlocked()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsShippingBlocked(bool $value)
 * @method bool getIsPaid()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsPaid(bool $value)
 * @method bool getIsSentToShopgate()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsSentToShopgate(bool $value)
 * @method bool getIsCancellationSentToShopgate()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsCancellationSentToShopgate(bool $value)
 * @method string getReceivedData()
 * @method Shopgate_Framework_Model_Shopgate_Order setReceivedData(string $value)
 * @method bool getIsTest()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsTest(bool $value)
 * @method bool getIsCustomerInvoiceBlocked()
 * @method Shopgate_Framework_Model_Shopgate_Order setIsCustomerInvoiceBlocked(bool $value)
 *
 */
class Shopgate_Framework_Model_Shopgate_Order extends Mage_Core_Model_Abstract
{
    /**
     * init model
     */
    protected function _construct()
    {
        $this->_init('shopgate/shopgate_order');
    }

    /**
     * @param Varien_Object $value
     * @return Shopgate_Framework_Model_Shopgate_Order
     */
    public function setOrder(Varien_Object $value)
    {
        return $this->setOrderId($value);
    }

    /**
     * @return ShopgateOrder
     */
    public function getShopgateOrderObject()
    {
        $data = $this->getReceivedData();
        $data = unserialize($data);

        return $data;
    }

    /**
     * get all shipments for the order
     *
     * @return array
     */
    public function getReportedShippingCollections()
    {
        $data = $this->getData("reported_shipping_collections");
        $data = unserialize($data);
        if (!$data) {
            $data = array();
        }

        return $data;
    }

    /**
     * @param array $collection_ids
     * @return Shopgate_Framework_Model_Shopgate_Order
     */
    public function setReportedShippingCollections(array $collection_ids)
    {
        $collection_ids = serialize($collection_ids);
        $this->setData("reported_shipping_collections", $collection_ids);

        return $this;
    }

    /**
     * @param null $order
     * @return bool
     */
    public function hasShippedItems($order = null)
    {
        if (!$order) {
            $order = $this->getOrder();
        }

        $shippedItems = false;
        foreach ($order->getItemsCollection() as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            if ($orderItem->getQtyShipped() > 0) {
                $shippedItems = true;
                break;
            }
        }

        return $shippedItems;
    }

    /**
     * return real order from shopgate order if exists
     *
     * @return Mage_Sales_Model_Order|NULL
     */
    public function getOrder()
    {
        if ($this->getOrderId() !== null) {
            return Mage::getModel("sales/order")->load($this->getOrderId());
        }

        return null;
    }

    /**
     * @param null $order
     * @return bool
     */
    public function hasItemsToShip($order = null)
    {
        if (!$order) {
            $order = $this->getOrder();
        }

        $itemsToShip = false;
        foreach ($order->getItemsCollection() as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            if ($orderItem->getQtyToShip() > 0 && $orderItem->getProductId() != null) {
                $itemsToShip = true;
                break;
            }
        }

        return $itemsToShip;
    }
}
