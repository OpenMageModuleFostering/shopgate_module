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
 * Forwarder for all Credit Card payment methods that contain CC in payment_method
 * Inherits from Simple class to use the first part of payment_method.
 * Meaning use Authn in Authn_CC to make Cc/Auth.php call
 *
 * Class Shopgate_Framework_Model_Payment_Cc
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc extends Shopgate_Framework_Model_Payment_Simple
{
    /**
     * Temp rewrite for edge case where AUTHN_CC needs to be
     * handled by AuthorizeCIM.
     *
     * @return false|Shopgate_Framework_Model_Payment_Abstract
     * @throws Exception
     */
    public function getModelByPaymentMethod()
    {
        if (Mage::getModel('shopgate/payment_cc_authncim', $this->getShopgateOrder())->isValid()) {
            $this->setPaymentMethod('AUTHNCIM_CC');
        }

        return parent::getModelByPaymentMethod();
    }
}