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
 * Class Shopgate_Framework_Model_Payment_Payone_Inv
 *
 * @author awesselburg <wesselburg@me.com>
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Payone_Inv
    extends Shopgate_Framework_Model_Payment_Payone_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYONE_CORE_MODEL_CONFIG_IDENTIFIER = 'payone_invoice';
    const PAYMENT_MODEL                       = 'payone_core/payment_method_invoice';
    const PAYMENT_IDENTIFIER                  = ShopgateOrder::PAYONE_INV;

    /**
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $info = $this->getShopgateOrder()->getPaymentInfos();

        if (isset($info['clearing_bankaccountholder'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankAccountholder($info['clearing_bankaccountholder']);
        }
        if (isset($info['clearing_bankcountry'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankCountry($info['clearing_bankcountry']);
        }
        if (isset($info['clearing_bankaccount'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankAccount($info['clearing_bankaccount']);
        }
        if (isset($info['clearing_bankcode'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankCode($info['clearing_bankcode']);
        }
        if (isset($info['clearing_bankcity'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankCity($info['clearing_bankcity']);
        }
        if (isset($info['clearing_bankname'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankName($info['clearing_bankname']);
        }
        if (isset($info['clearing_bankiban'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankIban(strtoupper($info['clearing_bankiban']));
        }
        if (isset($info['clearing_bankbic'])) {
            $this->getOrder()->getPayment()->setPayoneClearingBankBic(strtoupper($info['clearing_bankbic']));
        }

        return parent::manipulateOrderWithPaymentData($this->getOrder());
    }

    /**
     * Rewritten to add additional clearing parameters to response
     * 
     * @param null|Payone_Api_Response_Authorization_Approved|Payone_Api_Response_Preauthorization_Approved $response
     * @return Payone_Api_Response_Authorization_Approved|Payone_Api_Response_Preauthorization_Approved
     */
    protected function _createFakeResponse($response = null)
    {
        $info = $this->getShopgateOrder()->getPaymentInfos();

        $response = $this->_getPayoneResponse();

        /** @var Payone_Api_Response_Authorization_Approved | Payone_Api_Response_Preauthorization_Approved $response */
        foreach ($info as $key => $val) {
            switch ($key) {
                case 'clearing_bankaccount':
                    $response->setClearingBankaccount($info[$key]);
                    break;
                case 'clearing_bankcode':
                    $response->setClearingBankcode($info[$key]);
                    break;
                case 'clearing_bankcountry':
                    $response->setClearingBankcountry($info[$key]);
                    break;
                case 'clearing_bankname':
                    $response->setClearingBankname($info[$key]);
                    break;
                case 'clearing_bankaccountholder':
                    $response->setClearingBankaccountholder($info[$key]);
                    break;
                case 'clearing_bankcity':
                    $response->setClearingBankcity($info[$key]);
                    break;
                case 'clearing_bankiban':
                    $response->setClearingBankiban($info[$key]);
                    break;
                case 'clearing_bankbic':
                    $response->setClearingBankbic($info[$key]);
                    break;
            }
        }
        return parent::_createFakeResponse($response);
    }

}