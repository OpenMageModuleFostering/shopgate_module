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
 * Redirects to the proper bank payment class
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Prepay extends Shopgate_Framework_Model_Payment_Simple
{
    /**
     * If mage 1.7+ use native bank payment, else try Check Money Order.
     * Phoenix plugin takes priority if it's enabled
     * Note! Last one overwrites previous.
     *
     * @return false|Shopgate_Framework_Model_Payment_Abstract
     */
    public function getModelByPaymentMethod()
    {
        if ($this->_getConfigHelper()->getIsMagentoVersionLower1700() === false) {
            $native = Mage::getModel('shopgate/payment_simple_prepay_native', array($this->getShopgateOrder()));
            if ($native instanceof Shopgate_Framework_Model_Payment_Interface && $native->isValid()) {
                $this->setPaymentMethod('Native');
            }
        } else {
            $checkmo = Mage::getModel('shopgate/payment_simple_prepay_checkmo', array($this->getShopgateOrder()));
            if ($checkmo instanceof Shopgate_Framework_Model_Payment_Interface && $checkmo->isValid()) {
                $this->setPaymentMethod('Checkmo');
            }
        }

        $phoenix = Mage::getModel('shopgate/payment_simple_prepay_phoenix', array($this->getShopgateOrder()));
        if ($phoenix instanceof Shopgate_Framework_Model_Payment_Interface && $phoenix->isValid()) {
            $this->setPaymentMethod('Phoenix');
        }

        return parent::getModelByPaymentMethod();
    }

}