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
 * Redirector for COD payment methods
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Cod extends Shopgate_Framework_Model_Payment_Simple
{
    const MODULE_CONFIG = 'Phoenix_CashOnDelivery';

    /**
     * Checks Phoenix|MSP|Native COD payment methods
     * Note! Last one overwrites previous.
     *
     * @return false|Shopgate_Framework_Model_Payment_Abstract
     */
    public function getModelByPaymentMethod()
    {
        if ($this->_getConfigHelper()->getIsMagentoVersionLower1700() === false) {
            $class = Mage::getModel('shopgate/payment_simple_cod_native', $this->getShopgateOrder());
            if ($class instanceof Shopgate_Framework_Model_Payment_Interface && $class->isValid()) {
                $this->setPaymentMethod('Native');
            }
        }

        if ($this->isModuleActive()) {
            if (version_compare($this->_getVersion(), '1.0.8', '<')) {
                $this->setPaymentMethod('Phoenix107');
            } else {
                $this->setPaymentMethod('Phoenix108');
            }
        }

        $msp = Mage::getModel('shopgate/payment_simple_cod_msp', $this->getShopgateOrder());
        if ($msp instanceof Shopgate_Framework_Model_Payment_Interface && $msp->isValid()) {
            $this->setPaymentMethod('Msp');
        }

        return parent::getModelByPaymentMethod();
    }

}