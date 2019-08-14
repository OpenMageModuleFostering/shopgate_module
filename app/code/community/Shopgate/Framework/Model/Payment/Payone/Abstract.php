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
 * This class is a fallback for all PayOne payment.
 *
 * Class Shopgate_Framework_Model_Payment_Payone_Abstract
 *
 * @author awesselburg <wesselburg@me.com>
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Payone_Abstract extends Shopgate_Framework_Model_Payment_Abstract
{
    const PAYONE_CORE_MODEL_CONFIG_IDENTIFIER = false;

    const PAYMENT_IDENTIFIER = 'PAYONE';
    const XML_CONFIG_ENABLED = 'payone_license_key';
    const MODULE_CONFIG      = 'Payone_Core';

    /**
     * @var Payone_Core_Model_Config
     */
    protected $_config;

    /**
     * @var Payone_Core_Model_System_Config_Abstract
     */
    protected $_systemConfig;

    /** @var Varien_Object */
    protected $_statusMapping = false;

    /** @var null|int */
    protected $_methodId;

    /** @var null|bool */
    protected $_transExists;

    /**
     * Initialize Payone Factory on load
     */
    public function _construct()
    {
        parent::_construct();
        $this->_initConfigs();
    }

    /**
     * @return $this
     */
    protected function _initConfigs()
    {
        $factory = Mage::getModel('payone_core/factory');
        if (!$factory) {
            return false;
        }

        /**
         * prepare the system config model
         */
        switch ($this->getConstant('PAYONE_CORE_MODEL_CONFIG_IDENTIFIER')) {
            case Payone_Core_Model_System_Config_PaymentMethodCode::ONLINEBANKTRANSFER :
                $this->_systemConfig  = $factory->getModelSystemConfigOnlinebanktransferType();
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getOnlinebanktransfer()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::CREDITCARD :
                $this->_systemConfig  = $factory->getModelSystemConfigCreditCardType();
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getCreditcard()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::DEBITPAYMENT :
                $this->_systemConfig  = $factory->getModelSystemConfigPaymentMethodType();
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getDebitPayment()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::SAFEINVOICE :
                $this->_systemConfig  = $factory->getModelSystemConfigSafeInvoiceType();
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getSafeInvoice()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::ADVANCEPAYMENT :
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getAdvancepayment()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::INVOICE :
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getInvoice()
                );
                break;
            case Payone_Core_Model_System_Config_PaymentMethodCode::WALLET :
                $this->_systemConfig  = $factory->getModelSystemConfigWalletType();
                $this->_statusMapping = new Varien_Object(
                    $this->getConfig()->getGeneral()->getStatusMapping()->getWallet()
                );
                break;
        }

        return $this;
    }

    /**
     * @return Payone_Core_Model_Config
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $service = Mage::getSingleton('payone_core/service_initializeConfig');
            $service->setFactory(Mage::getModel('payone_core/factory'));
            $this->_config = $service->execute();
        }
        return $this->_config;
    }

    /**
     * @return Payone_Core_Model_System_Config_Abstract
     */
    public function getSystemConfig()
    {
        return $this->_systemConfig;
    }

    /**
     * @return ShopgateOrder
     */
    public function getShopgateOrder()
    {
        return $this->_shopgate_order;
    }

    /**
     * Left blank on purpose, no fallback
     *
     * @return false
     */
    public function getMethodType()
    {
        return $this->getPaymentModel()->getMethodType();
    }

    /**
     * @return bool
     */
    protected function _getConfigCode()
    {
        return false;
    }

    /**
     * @return Shopgate_Framework_Helper_Payment_Payone
     */
    protected function _getPaymentHelper()
    {
        return Mage::helper('shopgate/payment_payone');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Order
     */
    public function createNewOrder($quote)
    {
        $shopgateOrder  = $this->getShopgateOrder();
        $convert        = Mage::getModel('sales/convert_quote');
        $transaction    = Mage::getModel('core/resource_transaction');
        $SgPaymentInfos = $shopgateOrder->getPaymentInfos();

        if ($quote->getCustomerId()) {
            $transaction->addObject($quote->getCustomer());
        }
        $quote->setTotalsCollectedFlag(true);
        $transaction->addObject($quote);
        if ($quote->isVirtual()) {
            $order = $convert->addressToOrder($quote->getBillingAddress());
        } else {
            $order = $convert->addressToOrder($quote->getShippingAddress());
        }
        $order->setBillingAddress($convert->addressToOrderAddress($quote->getBillingAddress()));
        if ($quote->getBillingAddress()->getCustomerAddress()) {
            $order->getBillingAddress()->setCustomerAddress($quote->getBillingAddress()->getCustomerAddress());
        }
        if (!$quote->isVirtual()) {
            $order->setShippingAddress($convert->addressToOrderAddress($quote->getShippingAddress()));
            if ($quote->getShippingAddress()->getCustomerAddress()) {
                $order->getShippingAddress()->setCustomerAddress($quote->getShippingAddress()->getCustomerAddress());
            }
        }

        $order->setPayment($convert->paymentToOrderPayment($quote->getPayment()));
        $order->getPayment()->setTransactionId($quote->getPayment()->getLastTransId());

        $order->getPayment()->setLastTransId($quote->getPayment()->getLastTransId());
        $order->setPayoneTransactionStatus($SgPaymentInfos['status']);

        foreach ($quote->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $orderItem = $convert->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }
        $order->setQuote($quote);
        $order->setExtOrderId($quote->getPayment()->getTransactionId());
        $order->setCanSendNewEmailFlag(false);

        $order->getPayment()->setData('payone_config_payment_method_id', $this->_getMethodId());

        return $this->setOrder($order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentPayone = $this->getPaymentModel();

        if ($paymentPayone) {
            $this->getOrder()->getPayment()->setMethod($paymentPayone->getCode());
            $paymentPayone->setInfoInstance($this->getOrder()->getPayment());
            $this->getOrder()->getPayment()->setMethodInstance($paymentPayone);
        }

        /**
         * Bank account detail setter
         */
        $info = $this->getShopgateOrder()->getPaymentInfos();
        if (isset($info['bank_account'])) {
            foreach ($info['bank_account'] as $key => $value) {
                /** @var $field ShopgateOrderCustomField */
                switch ($key) {
                    case 'bic':
                        $this->getOrder()->getPayment()->setPayoneSepaBic($value);
                        break;
                    case 'bank_code':
                        $this->getOrder()->getPayment()->setPayoneBankCode($value);
                        break;
                    case 'iban':
                        $this->getOrder()->getPayment()->setPayoneSepaIban($value);
                        break;
                    case 'bank_account_number':
                        $this->getOrder()->getPayment()->setPayoneAccountNumber($value);
                        break;
                    case 'bank_account_holder':
                        $this->getOrder()->getPayment()->setPayoneAccountOwner($value);
                        break;
                }
            }
        }
        $this->getOrder()->save();
        $this->_addTransaction();
        $this->_addInvoice();

        return $this->getOrder();
    }

    /**
     * Crates PayOne transaction for refund purposes
     *
     * @throws Payone_Core_Exception_TransactionAlreadyExists
     */
    protected function _addTransaction()
    {
        /**
         * Faking a response from gateway
         */
        $request  = $this->_createFakeRequest();
        $response = $this->_createFakeResponse();

        /** @var Payone_Core_Model_Handler_Payment_Abstract $handler */
        $handler = $this->_getPayoneHandler();
        $handler->setConfigStore($this->getConfig());
        $handler->setPayment($this->getOrder()->getPayment());
        $handler->setRequest($request);
        $handler->handle($response);
    }

    /**
     * Creates an invoice if it's paid
     *
     * @throws Exception
     */
    protected function _addInvoice()
    {
        if ($this->getShopgateOrder()->getIsPaid()) {
            $info    = $this->getShopgateOrder()->getPaymentInfos();
            $invoice = $this->_getPaymentHelper()->createOrderInvoice($this->getOrder());
            $invoice->setIsPaid(true);
            $invoice->pay();
            $invoice->setTransactionId($info['txn_id']);
            $invoice->save();
            $this->getOrder()->addRelatedObject($invoice);
        }
    }

    /**
     * Creates fake initial request object
     *
     * @param null|Payone_Api_Request_Authorization|Payone_Api_Request_Preauthorization $request
     * @return Payone_Api_Request_Authorization|Payone_Api_Request_Preauthorization
     */
    protected function _createFakeRequest($request = null)
    {
        $factory = Mage::getModel('payone_core/factory');
        $info    = $this->getShopgateOrder()->getPaymentInfos();

        if (!$request) {
            if ($info['request_type'] === 'authorization') {
                $request = $factory->getRequestPaymentAuthorization();
            } else {
                $request = $factory->getRequestPaymentPreauthorize();
            }
        }

        $request->setClearingtype($info['clearing_type']);
        $request->setMode($info['mode']);
        $request->setMid($info['mid']);
        $request->setAid($info['aid']);
        $request->setPortalid($info['portalid']);

        return $request;
    }

    /**
     * Crete fake response object to feed PayOne handler
     *
     * @param null|Payone_Api_Response_Authorization_Approved|Payone_Api_Response_Preauthorization_Approved $response
     * @return Payone_Api_Response_Authorization_Approved|Payone_Api_Response_Preauthorization_Approved
     */
    protected function _createFakeResponse($response = null)
    {
        $info = $this->getShopgateOrder()->getPaymentInfos();

        if (!$response) {
            $response = $this->_getPayoneResponse();
        }

        foreach ($info as $key => $val) {
            switch ($key) {
                case 'txid':
                    $response->setTxid($info[$key]);
                    break;
                case 'userid':
                    $response->setUserid($info[$key]);
                    break;
                case 'status':
                    $response->setStatus($info[$key]);
                    break;
            }
        }
        return $response;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $data
     *
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $data)
    {
        $quote->getPayment()->setLastTransId($data['txid']);
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
        $paid    = $this->getShopgateOrder()->getIsPaid() ? 'paid' : 'appointed';
        $mapping = $this->_getPayoneStateMapping($paid);
        $message = $this->_getHelper()->__('[SHOPGATE] Using PayOne configured status');

        //status fallback
        if (!isset($mapping['state'], $mapping['status'])) {
            $mapping['state']  = Mage_Sales_Model_Order::STATE_PROCESSING;
            $mapping['status'] = $this->_getHelper()->getStatusFromState($mapping['state']);
            $message           = $this->_getHelper()->__('[SHOPGATE] Using default order status');
        }

        $magentoOrder->setState($mapping['state'], $mapping['status'], $message);
        $magentoOrder->setShopgateStatusSet(true);

        return $magentoOrder;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function _getPayoneStateMapping($key)
    {
        if ($this->_statusMapping instanceof Varien_Object) {
            return $this->_statusMapping->hasData($key)
                ? $this->_statusMapping->getData($key)
                : false;
        }

        return false;
    }

    /**
     * Different versions have
     * different config options
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (version_compare($this->_getVersion(), '3.3.0', '>=')) {
            return parent::isEnabled();
        }
        $val = Mage::getStoreConfig('payone_general/global/key');
        return !empty($val);
    }

    /**
     * Checks if config is found for this method
     * && transaction does not already exists
     * && payOne API response is APPROVED
     * Else fallback import with Shopgate Mobile Payment
     *
     * @return bool
     */
    public function checkGenericValid()
    {
        $methodId    = $this->_getMethodId();
        $transExists = $this->_getPayoneTransactionExist();
        $response    = $this->_getPayoneResponse();
        return !empty($methodId) && !$transExists && $response;
    }

    /**
     * Gets method ID if it is possible
     *
     * @return int|false
     */
    protected function _getMethodId()
    {
        if ($this->_methodId === null) {
            $this->_methodId = $this->_getPaymentHelper()
                                    ->getMethodId($this->getConfig(), $this->getMethodType());
        }
        return $this->_methodId;
    }

    /**
     * Checks if PayOne transaction already exists
     *
     * @return bool
     */
    protected function _getPayoneTransactionExist()
    {
        if (!is_null($this->_transExists)) {
            return $this->_transExists;
        }
        
        $info    = $this->getShopgateOrder()->getPaymentInfos();
        $factory = Mage::getModel('payone_core/factory');
        $this->_transExists = false;
        
        if (isset($info['txid']) && $factory) {
            $transaction = $factory->getModelTransaction();
            if ($transaction->load($info['txid'], 'txid')->hasData()) {
                $debug = $this->_getHelper()->__('PayOne transaction "%s" already exists', $transaction->getTxid());
                ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
                return $this->_transExists = true;
            }
        } else {
            $debug = $this->_getHelper()->__('Either "txid" or PayOne: Factory cannot be loaded');
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }
        
        return $this->_transExists;
    }

    /**
     * Retrieve PayOne response based on ShopgateOrder
     *
     * @return Payone_Api_Response_Authorization_Approved|Payone_Api_Response_Preauthorization_Approved|false
     */
    protected function _getPayoneResponse()
    {
        $info = $this->getShopgateOrder()->getPaymentInfos();
        if ($info['status'] == 'APPROVED') {
            if ($info['request_type'] === 'authorization') {
                return new Payone_Api_Response_Authorization_Approved();
            } else {
                return new Payone_Api_Response_Preauthorization_Approved();
            }
        }
        $debug = mage::helper('shopgate')->__('Order "status" was not approved');
        ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_ERROR);
        return false;
    }

    /**
     * Get proper handler
     *
     * @return Payone_Core_Model_Handler_Payment_Authorize|Payone_Core_Model_Handler_Payment_Preauthorize
     */
    private function _getPayoneHandler()
    {
        /** @var Payone_Core_Model_Factory $factory */
        $factory       = Mage::getModel('payone_core/factory');
        $paymentConfig = Mage::getModel('payone_core/config_payment_method');
        $info          = $this->getShopgateOrder()->getPaymentInfos();
        $type          = $info['request_type'];

        if ($type === 'authorization') {
            return $factory->getHandlerPaymentAuthorize($paymentConfig);
        }

        return $factory->getHandlerPaymentPreauthorize($paymentConfig);
    }
}