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
 * Search Order Model
 *
 * @category    Shopgate
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Search_Shopgate_Order extends Varien_Object
{
    /**
     * Load search results
     *
     * @return Shopgate_Framework_Model_Search_Shopgate_Order
     */
    public function load()
    {
        $arr = array();

        if (!$this->hasStart() || !$this->hasLimit() || !$this->hasQuery()) {
            $this->setResults($arr);
            return $this;
        }

        $collection = Mage::getModel('shopgate/shopgate_order')->getCollection()
                          ->addFieldToFilter("shopgate_order_number", array("like" => "%{$this->getQuery()}%"))
                          ->setCurPage($this->getStart())
                          ->setPageSize($this->getLimit());

        foreach ($collection as $sgOrder) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $sgOrder->getOrder();

            $arr[] = array(
                'id'               => 'order/1/' . $order->getId(),
                'type'             => Mage::helper('adminhtml')->__('Order'),
                'name'             => Mage::helper('adminhtml')->__('Shopgate Order #%s',
                                          $sgOrder->getShopgateOrderNumber()),
                'description'      => $order->getBillingFirstname() . ' ' . $order->getBillingLastname(),
                'form_panel_title' => Mage::helper('adminhtml')->__('Order #%s (%s)', $order->getIncrementId(),
                                          $order->getBillingFirstname() . ' ' . $order->getBillingLastname()),
                'url'              => Mage::helper('adminhtml')->getUrl(
                                          '*/sales_order/view',
                                          array('order_id' => $order->getId())),
            );
        }

        $this->setResults($arr);

        return $this;
    }
}
