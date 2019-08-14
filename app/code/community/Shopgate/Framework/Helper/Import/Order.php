<?php

/**
 * @deprecated  v2.9.19
 * @package     Shopgate_Framework_Helper_Import_Order
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 * @author      Konstantin Kirittsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Helper_Import_Order extends Mage_Core_Helper_Abstract
{
    /**
     * @deprecated  v2.9.19 handled in classes now
     * @param       string $paymentType
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function getMagentoPaymentMethod($paymentType)
    {
        $payment = null;

        switch ($paymentType) {
            case ShopgateOrder::SHOPGATE:
                $payment = Mage::getModel("shopgate/payment_shopgate");
                break;
            case ShopgateOrder::PAYPAL:
                $payment = Mage::getModel("paypal/standard");
                if (!$payment->isAvailable()) {
                    $payment = Mage::getModel("paypal/express");
                    if (!$payment->isAvailable()) {
                        $payment = Mage::getModel("shopgate/payment_mobilePayment");
                    }
                }
                break;
            case ShopgateOrder::COD:
                $payment = $this->_getCodPayment();
                break;
            case ShopgateOrder::PREPAY:
                $classExists = mageFindClassFile("Mage_Payment_Model_Method_Banktransfer");
                if ($classExists !== false && Mage::getStoreConfigFlag("payment/banktransfer/active")) {
                    $payment = Mage::getModel('payment/method_banktransfer');
                    break;
                }

                if ($this->_isModuleActive('Phoenix_BankPayment') || $this->_isModuleActive('Mage_BankPayment')) {
                    $payment = Mage::getModel("bankpayment/bankPayment");
                    break;
                }
                break;
            case ShopgateOrder::INVOICE:
                $payment = Mage::getModel("payment/method_purchaseorder");
                break;
            case ShopgateOrder::AMAZON_PAYMENT:
                if ($this->_isModuleActive('Creativestyle_AmazonPayments')) {
                    $payment = Mage::getModel('amazonpayments/payment_advanced');
                    break;
                }
                break;
            case ShopgateOrder::PP_WSPP_CC:
                if ($this->_isModuleActive('Mage_Paypal')) {
                    $payment = Mage::getModel('paypal/direct');
                    break;
                }
                break;
            case ShopgateOrder::SUE:
                $payment = $this->_getSuePayment();
                break;
            default:
                $payment = Mage::getModel("shopgate/payment_mobilePayment");
                break;
        }

        if (!$payment) {
            $payment = Mage::getModel("shopgate/payment_mobilePayment");
        }
        return $payment;
    }

    /**
     * @return MSP_CashOnDelivery_Model_Cashondelivery|null|Phoenix_CashOnDelivery_Model_CashOnDelivery|Mage_Payment_Model_Method_Cashondelivery
     */
    protected function _getCodPayment()
    {
        $payment = null;
        if ($this->_isModuleActive('Phoenix_CashOnDelivery')) {
            $version = Mage::getConfig()->getModuleConfig("Phoenix_CashOnDelivery")->version;
            if (version_compare($version, '1.0.8', '<')) {
                $payment = Mage::getModel("cashondelivery/cashOnDelivery");
            } else {
                $payment = Mage::getModel("phoenix_cashondelivery/cashOnDelivery");
            }
        }

        if ($this->_isModuleActive('MSP_CashOnDelivery')) {
            $payment = Mage::getModel('msp_cashondelivery/cashondelivery');
        }

        $classExists = mageFindClassFile('Mage_Payment_Model_Method_Cashondelivery');
        if ($classExists !== false && Mage::getStoreConfigFlag('payment/cashondelivery/active')) {
            $payment = Mage::getModel('payment/method_cashondelivery');
        }

        return $payment;
    }

    /**
     * @return null|Paymentnetwork_Pnsofortueberweisung_Model_Pnsofortueberweisung|Paymentnetwork_Pnsofortueberweisung_Model_Method_Sofort
     */
    protected function _getSuePayment()
    {
        $payment = null;
        if ($this->_isModuleActive('Paymentnetwork_Pnsofortueberweisung')) {
            $version = Mage::getConfig()->getModuleConfig("Paymentnetwork_Pnsofortueberweisung")->version;
            if (version_compare($version, '3.0.0', '>=')) {
                $payment = Mage::getModel('sofort/method_sofort');
            } else {
                $payment = Mage::getModel('pnsofortueberweisung/pnsofortueberweisung');
            }
        }
        return $payment;
    }

    /**
     * Helps with readability
     *
     * @param $moduleName
     * @return bool
     */
    protected function _isModuleActive($moduleName)
    {
        return Mage::getConfig()->getModuleConfig($moduleName)->is('active', 'true');
    }

    /**
     * Print comments inside order
     *
     * @param Mage_Sales_Model_Order $order
     * @param ShopgateOrder          $shopgateOrder
     * @return mixed
     */
    public function printCustomFieldComments($order, $shopgateOrder)
    {
        if (Mage::getStoreConfig(
            Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ORDER_CUSTOMFIELDS_TO_STATUSHISTORY,
            Mage::helper('shopgate/config')->getConfig()->getStoreViewId()
        )
        ) {
            $comment        = '';
            $customFieldSet = array();

            $oderCustomFields = $shopgateOrder->getCustomFields();
            if (!empty($oderCustomFields)) {
                $label = $this->_getHelper()->__('[SHOPGATE] Custom fields:');
                $customFieldSet[$label] = $oderCustomFields;
            }

            $deliveryCustomFields = $shopgateOrder->getDeliveryAddress()->getCustomFields();
            if (!empty($deliveryCustomFields)) {
                $label = $this->_getHelper()->__('Shipping Address fields:');
                $customFieldSet[$label] = $deliveryCustomFields;
            }

            $invoiceCustomFields = $shopgateOrder->getInvoiceAddress()->getCustomFields();
            if (!empty($invoiceCustomFields)) {
                $label = $this->_getHelper()->__('Billing Address fields:');
                $customFieldSet[$label] = $invoiceCustomFields;
            }

            foreach ($customFieldSet as $title => $set) {
                $comment .= '<strong>' . $title . '</strong><br/>';
                foreach ($set as $field) {
                    $comment .= '"' . addslashes(
                            $field->getLabel()
                        ) . '" => "' . addslashes($field->getValue()) . '"<br />';
                }
            }

            if (!empty($comment)) {
                $order->addStatusHistoryComment($comment, false);
            }
        }

        return $order;
    }

    /**
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }
}