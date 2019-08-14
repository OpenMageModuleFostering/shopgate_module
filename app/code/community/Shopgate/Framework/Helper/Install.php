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
 * User: pliebig
 * Date: 07.03.14
 * Time: 12:25
 * E-Mail: p.liebig@me.com
 */

/**
 * install helper
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Helper_Install extends Mage_Core_Helper_Abstract
{
    /**
     * request url
     */
    const URL_TO_UPDATE_SHOPGATE = 'https://api.shopgate.com/log';

    /**
     * interface installation action
     */
    const INSTALL_ACTION = "interface_install";

    /**
     * type community string
     */
    const TYPE_COMMUNITY = "Community";

    /**
     * type enterprise string
     */
    const TYPE_ENTERPRISE = "Enterprise";

    /**
     * type magento go string
     */
    const TYPE_GO = "Go";

    /**
     * hidden uid field
     */
    const XML_PATH_HIDDEN_UID_FIELD_SHOPGATE = 'shopgate/uid';

    /**
     * @var null
     */
    protected $_orders = null;

    /**
     * @var null
     */
    protected $_date = null;

    /**
     * @var array
     */
    protected $_orderIds = array();

    /**
     * read connection for DB
     *
     * @var Mage_Core_Model_Resource | null
     */
    protected $_resource = null;

    /**
     * @var null | Varien_Db_Adapter_Interface
     */
    protected $_adapter = null;

    /**
     *
     */
    public function updateShopgateSystem($type = self::INSTALL_ACTION)
    {
        $this->_date = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . "-1 months"));
        $subshops    = array();

        /** @var $store Mage_Core_Model_Store */
        foreach (Mage::getModel('core/store')->getCollection() as $store) {

            $storeId    = $store->getId();
            $subshops[] = array(
                'uid'                 => $storeId,
                'name'                => $store->getFrontendName(),
                'url'                 => $this->_getConfigData('web/unsecure/base_url', 'stores', $storeId),
                'contact_name'        => $this->_getConfigData('trans_email/ident_general/name', 'stores', $storeId),
                'contact_phone'       => $this->_getConfigData('general/store_information/phone', 'stores', $storeId),
                'contact_email'       => $this->_getConfigData('trans_email/ident_general/email', 'stores', $storeId),
                'stats_items'         => $this->_getItems($storeId),
                'stats_categories'    => $this->_getCategories($store->getRootCategoryId()),
                'stats_orders'        => $this->_getOrders($storeId),
                'stats_acs'           => $this->_calculateAverage($storeId),
                'stats_currency'      => $this->_getConfigData('currency/options/default', 'stores', $storeId),
                'stats_unique_visits' => $this->_getVisitors($storeId),
                'stats_mobile_visits' => 0
            );
        }

        $data = array(
            'action'             => $type,
            'uid'                => $this->_getUid(),
            'plugin_version'     => $this->_getPluginVersion(),
            'shopping_system_id' => $this->_getShopSystemType(),
            'subshops'           => $subshops
        );

        Mage::getConfig()->saveConfig(self::XML_PATH_HIDDEN_UID_FIELD_SHOPGATE, $data['uid']);

        try {
            $client = new Zend_Http_Client(self::URL_TO_UPDATE_SHOPGATE);
            $client->setParameterPost($data);
            $client->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            Mage::log("Shopgate_Framework Message: " . self::URL_TO_UPDATE_SHOPGATE . " could not be reached.", Zend_Log::INFO, 'shopgate.log');
        }
    }

    /**
     * getStoreConfig not working in installer, so need to read from db
     *
     * @param        $path
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    protected function _getConfigData($path, $scope = 'default', $scopeId = 0)
    {
        if (!$this->_resource) {
            $this->_resource = Mage::getSingleton('core/resource');
            $this->_adapter  = $this->_resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        }

        $table = $this->_resource->getTableName('core/config_data');

        $select = $this->_adapter->select()
                                 ->from($table)
                                 ->columns('value')
                                 ->where('path = "' . $path . '" and scope="' . $scope . '" and scope_id="' . $scopeId . '"');

        $result = $this->_adapter->fetchRow($select);

        if (!$result['value'] && $scope != 'default') {
            $result['value'] = $this->_getConfigData($path);
        }

        return $result['value'];
    }

    /**
     * get plugin version
     *
     * @return string
     */
    protected function _getPluginVersion()
    {
        return (string)Mage::getConfig()->getModuleConfig("Shopgate_Framework")->version;
    }

    /**
     * get shop system number ( internal usage )
     *
     * @return int|null
     */
    protected function _getShopSystemType()
    {
        switch ($this->_getEdition()) {
            case self::TYPE_COMMUNITY:
                return 76;
                break;
            case self::TYPE_ENTERPRISE:
                return 228;
                break;
            case self::TYPE_GO:
                return 229;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * return magento type (edition)
     *
     * @return string
     */
    protected function _getEdition()
    {
        $edition = self::TYPE_COMMUNITY;

        if (method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
        } else {
            $dir = mage::getBaseDir('code') . DS . 'core' . DS . self::TYPE_ENTERPRISE;
            if (file_exists($dir)) {
                $edition = self::TYPE_ENTERPRISE;
            }
        }

        return $edition;
    }

    /**
     * get product count unfiltered
     *
     * @param int $storeId
     *
     * @return int
     */
    protected function _getItems($storeId)
    {
        return Mage::getModel('catalog/product')
                   ->getCollection()
                   ->addStoreFilter($storeId)
                   ->addAttributeToSelect('id')
                   ->getSize();
    }

    /**
     * get categories count
     *
     * @param int $rootCatId
     *
     * @return int
     */
    protected function _getCategories($rootCatId)
    {
        return Mage::getResourceModel('catalog/category')->getChildrenCount($rootCatId);
    }

    /**
     * get amount of orders
     *
     * @param int $storeId
     *
     * @return int|null
     */
    protected function _getOrders($storeId)
    {
        /** @var Mage_Eav_Model_Entity_Collection_Abstract $collection */
        $collection = Mage::getResourceModel('sales/order_collection')
                          ->addFieldToFilter('store_id', $storeId)
                          ->addFieldToFilter(
                          'created_at',
                          array(
                              array(
                                  'gteq' => $this->_date
                              )
                          )
            )->addAttributeToSelect('grand_total');

        return $collection->getSize();
    }

    /**
     * @param int $storeId
     *
     * @return float result
     */
    protected function _calculateAverage($storeId)
    {
        $collection = Mage::getResourceModel('sales/order_collection')
                          ->addFieldToFilter('store_id', $storeId)
                          ->addFieldToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
                          ->addAttributeToSelect('grand_total');
        $collection->getSelect()->from(null, array('average' => 'AVG(grand_total)'));
        $result = $this->_adapter->fetchRow($collection->getSelect()->assemble());
        if (!$result['average']) {
            $result['average'] = 0;
        }
        return round($result['average'], 2);
    }

    /**
     * get visitor data unfiltered
     *
     * @param int $storeId
     *
     * @return int
     */
    protected function _getVisitors($storeId)
    {
        $result = Mage::getResourceModel('log/aggregation')->getCounts($this->_date, date("Y-m-d H:i:s"), $storeId);
        if (!$result['visitors']) {
            $result['visitors'] = 0;
        }

        return $result['visitors'];
    }

    /**
     * get uid to clarify identification in home system
     *
     * @return string
     */
    protected function _getUid()
    {
        $key  = (string)Mage::getConfig()->getNode('global/crypt/key');
        $salt = $this->_getShopSystemType();
        if (!$salt) {
            $salt = "5h0p6473.c0m";
        }
        return md5($key . $salt);
    }

    /**
     * Public wrapper method for _getUid()
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_getUid();
    }
}
