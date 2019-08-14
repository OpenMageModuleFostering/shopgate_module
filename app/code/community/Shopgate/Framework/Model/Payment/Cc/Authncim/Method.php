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
 * Extending method class for gateway initialization
 *
 * Class Shopgate_Framework_Model_Payment_Cc_Authncim_Method
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_Authncim_Method extends ParadoxLabs_AuthorizeNetCim_Model_Method
{
    /**
     * Call our rewritten Gateway and initialize login credentials
     *
     * @return null|Shopgate_Framework_Model_Payment_Cc_Authncim_Gateway
     */
    public function gateway()
    {
        if (is_null($this->_gateway)) {
            $this->_gateway = Mage::getModel('shopgate/payment_cc_authncim_gateway');
            $this->_gateway->init(
                array(
                    'login'      => $this->getConfigData('login'),
                    'password'   => $this->getConfigData('trans_key'),
                    'secret_key' => $this->getConfigData('secrey_key'),
                    'test_mode'  => $this->getConfigData('test')
                )
            );
        }

        return $this->_gateway;
    }
}