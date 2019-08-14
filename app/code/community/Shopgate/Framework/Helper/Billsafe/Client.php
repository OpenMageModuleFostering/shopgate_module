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
class Shopgate_Framework_Helper_Billsafe_Client extends Netresearch_Billsafe_Model_Client
{
    /**
     * @inheritdoc
     */
    public function reportShipment(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $originalId = $shipment->getOrder()->getIncrementId();
        $shopgateId = $this->getShopgateOrderNumber($shipment->getOrder());
        $shipment->getOrder()->setIncrementId($shopgateId);
        parent::reportShipment($shipment);
        $shipment->getOrder()->setIncrementId($originalId);
        $shipment->getOrder()->setData('is_shopgate_order', true);

        return $this;
    }

    /**
     * @inheritdoc
     * @param Mage_Sales_Model_Order $order
     */
    public function getPaymentInstruction($order)
    {
        $originalId = $order->getIncrementId();
        $shopgateId = $this->getShopgateOrderNumber($order);
        $order->setIncrementId($shopgateId);
        $instruction = parent::getPaymentInstruction($order);
        $order->setIncrementId($originalId);

        return $instruction;
    }

    /**
     * @inheritdoc
     */
    public function updateArticleList(Mage_Sales_Model_Order $order, $context)
    {
        $originalId = $order->getIncrementId();
        $shopgateId = $this->getShopgateOrderNumber($order);
        $order->setIncrementId($shopgateId);
        parent::updateArticleList($order, $context);
        $order->setIncrementId($originalId);

        return $this;
    }

    /**
     * Retrieves the Shopgate order number, else retrieves the
     * real ID. This ensures safety of this rewrite.
     *
     * @param Mage_Sales_Model_Order | Varien_Object $mageOrder - comes in as Varien on import, order from ship observer
     * @return string | int
     */
    private function getShopgateOrderNumber(Varien_Object $mageOrder)
    {
        $sgOrder = Mage::getModel('shopgate/shopgate_order')->load($mageOrder->getId(), 'order_id');

        return $sgOrder->getShopgateOrderNumber() ? $sgOrder->getShopgateOrderNumber() : $mageOrder->getIncrementId();
    }
}