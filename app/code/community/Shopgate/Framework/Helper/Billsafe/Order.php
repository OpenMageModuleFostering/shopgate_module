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
 * User: awesselburg
 * Date: 07.04.15
 * Time: 15:29
 * E-Mail: awesselburg <wesselburg@me.com>
 */

class Shopgate_Framework_Helper_Billsafe_Order
    extends Netresearch_Billsafe_Helper_Order
{
    /** @var  Mage_Sales_Model_Order */
    protected $_order;

    /**
     * @param Mage_Sales_Model_Abstract $entity
     * @param Mage_Sales_Model_Order    $order
     * @param string                    $context
     *
     * @return mixed
     */
    protected function getAllOrderItems($entity, $order, $context)
    {
        $this->_order = $order;

        if ($context == self::TYPE_RS) {
            return $entity->getAllItems();
        }
        return $order->getAllItems();
    }

    /**
     * @param $orderItems
     * @param $amount
     * @param $taxAmount
     * @param $context
     *
     * @return array
     */
    protected function getOrderItemData($orderItems, $amount, $taxAmount, $context)
    {
        if (!$this->_order->getIsShopgateOrder()) {
            /**
             * no shopgate billsafe order - use original method
             */
            return parent::getOrderItemData($orderItems, $amount, $taxAmount, $context);
        }

        $data = array(
            'amount' => 0,
            'tax_amount' => 0
        );

        $addedBundleProduct = array();

        foreach ($orderItems as $item) {

            if ($this->getHelper()->isFeeItem($item)) {
                $data['payment_fee_item'] = $item;
                continue;
            }
            $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();

            if (self::TYPE_VO == $context) {
                $qty = (int) $item->getQtyShipped();
            }

            if ($context == self::TYPE_RS) {
                $qty = $item->getQty();
                if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                    $item = $item->getOrderItem();
                }
            }

            if ($item->isDummy() || $qty <= 0) {
                continue;
            }

            /**
             * manipulate number
             */
            $productOptions = $item->getProductOptions();
            $skip = false;

            if ($item->getParentItemId()) {

                $parentItem = Mage::getModel('sales/order_item')->load($item->getParentItemId());
                $name = $parentItem->getName();
                $grossPrice = $parentItem->getPriceInclTax();

                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE :

                        if (in_array($parentItem->getItemId(), $addedBundleProduct)) {
                            $skip = true;
                        }

                        $number = $productOptions['info_buyRequest']['product'];
                        array_push($addedBundleProduct, $parentItem->getItemId());
                        break;
                }

            } else {

                switch ($item->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE :
                        $simpleSalesOrderItem = Mage::getModel('sales/order_item')
                            ->getCollection()
                            ->addFieldToFilter('parent_item_id', array('eq' => $item->getItemId()))
                            ->addFieldToFilter('order_id', array('eq' => $item->getOrderId()));

                        $number = sprintf(
                            '%d-%d',
                            $productOptions['info_buyRequest']['product'],
                            $simpleSalesOrderItem->getFirstItem()->getProductId());

                        break;
                    default :
                        $number = $productOptions['info_buyRequest']['product'];
                        break;

                }

                $name = $item->getName();
                $grossPrice = $item->getPriceInclTax();
            }

            if (!$skip) {
                $data['data'][] = array(
                    'number' => substr($number, 0, 50),
                    'name' => $name,
                    'type' => 'goods',
                    'quantity' => (int) $qty,
                    'quantityShipped' => (int) $item->getQtyShipped(),
                    'grossPrice' => $this->getHelper()->format($grossPrice),
                    'tax' => $this->getHelper()->format(
                        $item->getTaxPercent()
                    ),
                );
                $data['amount'] += $amount + $grossPrice * $qty;
                $data['tax_amount'] += $taxAmount + $item->getTaxAmount() * $qty;
            }
        }

        return $data;
    }
}