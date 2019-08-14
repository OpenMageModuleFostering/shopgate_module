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
 * payment helper abstract
 *
 * @package     Shopgate_Framework_Helper_Payment_Abstract
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */ 

class Shopgate_Framework_Helper_Payment_Abstract extends Mage_Core_Helper_Abstract
{
    /**
     * string for cc number in ipn data
     */
    const SHOPGATE_CC_STRING = 'masked_number';

    /**
     * string for cc type in ipn data
     */
    const SHOPGATE_CC_TYPE_STRING = 'type';

    /**
     * string for holder in ipn data
     */
    const SHOPGATE_CC_HOLDER_STRING = 'holder';

    /**
     * Create new invoice with maximum qty for invoice for each item
     * register this invoice and capture
     *
     * @param $order Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function createOrderInvoice($order)
    {
        $invoice = $order->prepareInvoice();
        $invoice->register();
        return $invoice;
    }
}