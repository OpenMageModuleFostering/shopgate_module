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
class Shopgate_Framework_Model_Payment_Abstract
    extends Mage_Core_Model_Abstract
    implements Shopgate_Framework_Model_Interfaces_Modules_Validator
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

        $shopgateOrder = current($this->_data);
        if (!$shopgateOrder instanceof ShopgateOrder) {
            $error = $this->_getHelper()->__('Incorrect class provided to: %s::_constructor()', get_class($this));
            ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
            throw new Exception($error);
        }

        return $this->setShopgateOrder($shopgateOrder);
    }

    /**
     * @param $shopgateOrder ShopgateOrder
     *
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
     *
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
     *
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
        $constant = $this->getConstant('MODULE_CONFIG');

        return Mage::getConfig()->getModuleConfig($constant)->version;
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
        $payment = $this->getConstant('PAYMENT_IDENTIFIER');
        $flag    = $this->getPaymentMethod() === $payment;

        if (!$flag) {
            $debug = $this->_getHelper()->__(
                'Payment method "%s" does not equal to identifier "%s" in class "%s"',
                $this->getPaymentMethod(),
                $payment,
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
        $config  = $this->getConstant('XML_CONFIG_ENABLED');
        $val     = Mage::getStoreConfig($config);
        $enabled = !empty($val);
        if (!$enabled) {
            $debug = $this->_getHelper()->__(
                'Enabled check by path "%s" was evaluated as empty: "%s" in class "%s"',
                $config,
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
        $config = $this->getConstant('MODULE_CONFIG');
        $active = Mage::getConfig()->getModuleConfig($config)->is('active', 'true');

        if (!$active) {
            $debug = $this->_getHelper()->__(
                'Module by config "%s" was not active in class "%s"',
                $config,
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
     * Set up function to be rewritten by payment
     * methods if anything needs to be set up before
     * general logic runs
     */
    public function setUp()
    {
    }

    /**
     * Default order creation if no payment matches
     *
     * @param Mage_Sales_Model_Quote $quote
     *
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
     *
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
     * @param array                  $info
     *
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
     *
     * @return mixed
     */
    public function setOrderStatus($magentoOrder)
    {
        $paid    = $this->getConstant('XML_CONFIG_STATUS_PAID');
        $notPaid = $this->getConstant('XML_CONFIG_STATUS_NOT_PAID');

        if ($this->getShopgateOrder()->getIsPaid()) {
            $status = Mage::getStoreConfig($paid, $magentoOrder->getStoreId());
        } else {
            if ($notPaid) {
                $status = Mage::getStoreConfig($notPaid, $magentoOrder->getStoreId());
            } else {
                $status = Mage::getStoreConfig($paid, $magentoOrder->getStoreId());
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
        $payment = $this->getConstant('PAYMENT_MODEL');
        $model   = Mage::getModel($payment);
        if (!$model) {
            $debug = $this->_getHelper()->__(
                'Could not find PAYMENT_MODEL %s in class %s',
                $payment,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
            $model = Mage::getModel(self::PAYMENT_MODEL);
        }

        return $model;
    }

    /**
     * Manipulation of new magento order, BEFORE payment is processed
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _beforeOrderManipulate($magentoOrder)
    {
        $shopgateOrder = $this->getShopgateOrder();

        if ($this->_getHelper()->oneCentBugDetected($shopgateOrder, $magentoOrder)) {
            $fixedOrderAmount = $shopgateOrder->getAmountComplete();
            $magentoOrder->setBaseTotalDue($fixedOrderAmount);
            $magentoOrder->setTotalDue($fixedOrderAmount);
            $magentoOrder->setBaseGrandTotal($fixedOrderAmount);
            $magentoOrder->setGrandTotal($fixedOrderAmount);
        }

        return $magentoOrder;
    }

    /**
     * Manipulation of new magento order, AFTER payment is processed
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _afterOrderManipulate($magentoOrder)
    {
        $shopgateOrder = $this->getShopgateOrder();

        if ($this->_getHelper()->oneCentBugDetected($shopgateOrder, $magentoOrder)) {
            $fixedOrderAmount = $shopgateOrder->getAmountComplete();
            $magentoOrder->getPayment()->setAmountOrdered($fixedOrderAmount);
            $magentoOrder->getPayment()->setBaseAmountOrdered($fixedOrderAmount);

            if ($magentoOrder->getInvoiceCollection()->count() == 1) {
                foreach ($magentoOrder->getInvoiceCollection() as $invoice) {
                    $invoice->setGrandTotal($magentoOrder->getGrandTotal());
                    $invoice->setBaseGrandTotal($magentoOrder->getBaseGrandTotal());

                    $magentoOrder->setBaseTotalInvoiced($invoice->getBaseGrandTotal());
                    $magentoOrder->setTotalInvoiced($invoice->getGrandTotal());
                    $magentoOrder->setTotalPaid($invoice->getGrandTotal());
                    $magentoOrder->setBaseTotalPaid($magentoOrder->getBaseTotalInvoiced());
                }
            }
        }

        return $magentoOrder;
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

    /**
     * Added support for PHP version 5.2
     * constant retrieval
     *
     * @param string $input
     *
     * @return mixed
     */
    protected final function getConstant($input)
    {
        $configClass = new ReflectionClass($this);

        return $configClass->getConstant($input);
    }
}