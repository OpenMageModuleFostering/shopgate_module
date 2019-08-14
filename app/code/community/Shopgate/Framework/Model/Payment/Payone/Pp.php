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
 * Class Shopgate_Framework_Model_Payment_Payone_Pp
 *
 * @author awesselburg <wesselburg@me.com>
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Payone_Pp
    extends Shopgate_Framework_Model_Payment_Payone_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYONE_CORE_MODEL_CONFIG_IDENTIFIER = 'payone_wallet';
    const PAYMENT_MODEL                       = 'payone_core/payment_method_wallet';
    const PAYMENT_IDENTIFIER                  = ShopgateOrder::PAYONE_PP;

    /**
     * @return string
     */
    protected function _getConfigCode()
    {
        return Payone_Api_Enum_WalletType::PAYPAL_EXPRESS;
    }

    /**
     * Creates an invoice for the order
     *
     * @throws Exception
     */
    protected function _addInvoice()
    {
        if ($this->getShopgateOrder()->getIsPaid()) {
           parent::_addInvoice();
        } else {
            $info    = $this->getShopgateOrder()->getPaymentInfos();
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($this->getOrder());
            $invoice->setIsPaid(false);
            $invoice->setTransactionId($info['txn_id']);
            $invoice->save();
            $this->getOrder()->addRelatedObject($invoice);
        }
    }
}