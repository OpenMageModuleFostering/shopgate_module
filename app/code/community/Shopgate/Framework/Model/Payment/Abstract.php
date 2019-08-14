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
 * User: pliebig
 * Date: 02.12.14
 * Time: 09:58
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */
 
 /**
 * abstracht payment model
 *
 * @package     Shopgate_Framework_Model_Payment_Abstract
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */ 

class Shopgate_Framework_Model_Payment_Abstract 
{
    /**
     * @var null|Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * @return Shopgate_Framework_Helper_Payment_Abstract
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_abstract');
    }
}