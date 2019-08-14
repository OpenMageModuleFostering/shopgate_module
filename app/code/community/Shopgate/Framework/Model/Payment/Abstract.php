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
 * Abstract payment model
 *
 * @package     Shopgate_Framework_Model_Payment_Abstract
 * @author      Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Abstract extends Mage_Core_Model_Abstract
{
    /**
     * Has to match the payment_method coming from API call
     */
    const PAYMENT_IDENTIFIER = '';

    /**
     * Model code of the class that inherits mage's Payment_Method_Abstract
     * Defaults to Shopgate's Mobile payment block
     */
    const PAYMENT_MODEL = 'shopgate/payment_mobilePayment';

    /**
     * The config path to module enabled
     */
    const XML_CONFIG_ENABLED = '';

    /**
     * The config path to module's paid status
     */
    const XML_CONFIG_STATUS_PAID = '';

    /**
     * The config path to module's not paid status
     */
    const XML_CONFIG_STATUS_NOT_PAID = '';

    /**
     * The name of the module, as defined in etc/modules/*.xml
     */
    const MODULE_CONFIG = '';

    /**
     * @var null|Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Comes from payment_type of API
     */
    protected $_payment_method;

    /**
     * Shopgate order inserted upon instantiation
     *
     * @var ShopgateOrder
     */
    protected $_shopgate_order;

    /**
     * $this->_data contains constructor param
     * Pass it into the Mage:getModel('',$param)
     *
     * @return ShopgateOrder
     * @throws Exception
     */
    public function _construct()
    {
        if ($this->_shopgate_order) {
            return $this->_shopgate_order;
        }

        $shopgateOrder = $this->_data;
        if (!$shopgateOrder instanceof ShopgateOrder) {
            $error = $this->_getHelper()->__('Incorrect class provided to: %s::_constructor()', get_class($this));
            ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
            throw new Exception($error);
        }

        return $this->setShopgateOrder($shopgateOrder);
    }

    /**
     * @param $shopgateOrder ShopgateOrder
     * @return $this
     */
    public function setShopgateOrder(ShopgateOrder $shopgateOrder)
    {
        $this->_shopgate_order = $shopgateOrder;
        return $this;
    }

    /**
     * @return ShopgateOrder
     */
    public function getShopgateOrder()
    {
        return $this->_shopgate_order;
    }

    /**
     * @param $paymentMethod string
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->_payment_method = $paymentMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        if (!$this->_payment_method) {
            $this->_payment_method = $this->getShopgateOrder()->getPaymentMethod();
        }
        return $this->_payment_method;
    }

    /**
     * Magento order getter
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Helps initialize magento order
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        return $this->_order = $order;
    }

    /**
     * Get version of plugin
     *
     * @return mixed
     */
    protected function _getVersion()
    {
        return Mage::getConfig()->getModuleConfig($this::MODULE_CONFIG)->version;
    }

    /**
     * ===========================================
     * ============= Active Checkers =============
     * ===========================================
     */

    /**
     * All around check for whether module is the one to use
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->isPayment() && $this->isEnabled() && $this->isModuleActive() && $this->checkGenericValid();
    }

    /**
     * Checks that api->payment_method is equals to class constant
     *
     * @return bool
     */
    public function isPayment()
    {
        $flag = $this->getPaymentMethod() === $this::PAYMENT_IDENTIFIER;

        if (!$flag) {
            $debug = $this->_getHelper()->__(
                'Payment method "%s" does not equal to identifier "%s" in class "%s"',
                $this->getPaymentMethod(),
                $this::PAYMENT_IDENTIFIER,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $flag;
    }

    /**
     * Checks store config to be active
     *
     * @return bool
     */
    public function isEnabled()
    {
        $val     = Mage::getStoreConfig($this::XML_CONFIG_ENABLED);
        $enabled = !empty($val);
        if (!$enabled) {
            $debug = $this->_getHelper()->__(
                'Enabled check by path "%s" was evaluated as empty: "%s" in class "%s"',
                $this::XML_CONFIG_ENABLED,
                $val,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }
        return $enabled;
    }

    /**
     * Checks module node to be active
     *
     * @return mixed
     */
    public function isModuleActive()
    {
        $active = Mage::getConfig()->getModuleConfig($this::MODULE_CONFIG)->is('active', 'true');

        if (!$active) {
            $debug = $this->_getHelper()->__(
                'Module by config "%s" was not active in class "%s"',
                $this::MODULE_CONFIG,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $active;
    }

    /**
     * Implement any custom validation
     *
     * @return bool
     */
    public function checkGenericValid()
    {
        return true;
    }

    /**
     * ===========================================
     * ======== Payment necessary methods ========
     * ===========================================
     */

    /**
     * Default order creation if no payment matches
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function createNewOrder($quote)
    {
        $service = Mage::getModel('sales/service_quote', $quote);
        if (!Mage::helper("shopgate/config")->getIsMagentoVersionLower15()) {
            $service->submitAll();
            return $this->setOrder($service->getOrder());
        } else {
            return $this->setOrder($service->submit());
        }
    }

    /**
     * Generic order manipulation, taken originally from Plugin::_setOrderPayment()
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($magentoOrder)
    {
        $shopgateOrder = $this->getShopgateOrder();

        if ($shopgateOrder->getIsPaid()) {

            if ($magentoOrder->getBaseTotalDue()) {
                $magentoOrder->getPayment()->setShouldCloseParentTransaction(true);
                $magentoOrder->getPayment()->registerCaptureNotification($shopgateOrder->getAmountComplete());

                $magentoOrder->addStatusHistoryComment($this->_getHelper()->__("[SHOPGATE] Payment received."), false)
                             ->setIsCustomerNotified(false);
            }
        }

        $magentoOrder->getPayment()->setLastTransId($shopgateOrder->getPaymentTransactionNumber());

        return $magentoOrder;
    }

    /**
     * Default quote prepare handler
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param                        $info
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $info)
    {
        return $quote;
    }

    /**
     * Setting for default magento status
     *
     * @param $magentoOrder Mage_Sales_Model_Order
     * @return mixed
     */
    public function setOrderStatus($magentoOrder)
    {
        if ($this->getShopgateOrder()->getIsPaid()) {
            $status = Mage::getStoreConfig($this::XML_CONFIG_STATUS_PAID, $magentoOrder->getStoreId());
        } else {
            if ($this::XML_CONFIG_STATUS_NOT_PAID) {
                $status = Mage::getStoreConfig($this::XML_CONFIG_STATUS_NOT_PAID, $magentoOrder->getStoreId());
            } else {
                $status = Mage::getStoreConfig($this::XML_CONFIG_STATUS_PAID, $magentoOrder->getStoreId());
            }
        }

        if ($status) {
            $state   = $this->_getHelper()->getStateForStatus($status);
            $message = $this->_getHelper()->__('[SHOPGATE] Using native plugin status');
            $magentoOrder->setState($state, $status, $message);
            $magentoOrder->setShopgateStatusSet(true);
        }

        return $magentoOrder;
    }

    /**
     * Returns the payment model of a class,
     * else falls back to mobilePayment 
     *
     * @return mixed
     */
    public function getPaymentModel()
    {
        $model = Mage::getModel($this::PAYMENT_MODEL);
        if (!$model) {
            $debug = $this->_getHelper()->__(
                'Could not find PAYMENT_MODEL %s in class %s',
                $this::PAYMENT_MODEL,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
            $model = Mage::getModel(self::PAYMENT_MODEL);
        }
        return $model;
    }

    /**
     * =======================================
     * ============ Helpers ==================
     * =======================================
     */

    /**
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }

    /**
     * @return Shopgate_Framework_Helper_Payment_Abstract
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_abstract');
    }

    /**
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('shopgate/config');
    }
}