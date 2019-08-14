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
 * This class supports magento < 1.6.0 import for Amazon Payments
 */
class Shopgate_Framework_Model_Payment_Simple_Mws_Mws15
    extends Shopgate_Framework_Model_Payment_Simple_Mws_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    /**
     * Supported payment type
     *
     * @return string
     */
    protected function _getTransactionType()
    {
        return Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT;
    }

    /**
     * Rewrite to be empty, mage 1400 does not have this function
     * Mage 1501 throws an exception as AmazonPayment plugin does not
     * support it
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return $this
     */
    protected function _importTransactionInfo($transaction)
    {
        return $this;
    }
}