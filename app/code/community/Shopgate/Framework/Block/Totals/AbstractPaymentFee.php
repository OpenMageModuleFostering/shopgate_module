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
 * Class Shopgate_Framework_Block_Totals_AbstractPaymentFee
 */
class Shopgate_Framework_Block_Totals_AbstractPaymentFee extends Mage_Core_Block_Abstract
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @var string
     */
    protected $_code = 'shopgate_payment_fee';

    /**
     * add fee to order detail view
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent       = $this->getParentBlock();
        $this->_order = $parent->getOrder();

        if ($this->_order->getShopgatePaymentFee()) {
            $fee = new Varien_Object();
            $fee->setLabel($this->__('Payment Fee'));
            $fee->setValue($this->_order->getShopgatePaymentFee());
            $fee->setBaseValue($this->_order->getBaseShopgatePaymentFee());
            $fee->setCode($this->getCode());

            $parent->addTotalBefore($fee, 'tax');
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getCode()
    {
        return $this->_code;
    }
}