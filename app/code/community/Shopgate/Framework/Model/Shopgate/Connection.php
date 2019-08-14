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
 * User: Steffen Meuser
 * Date: 30.05.2014
 * Time: 09:16
 * E-Mail: steffen.meuser@shopgate.com
 */

/**
 * @package      Shopgate_Framework
 * @author       Shopgate GmbH Butzbach
 */
class Shopgate_Framework_Model_Shopgate_Connection extends Mage_Core_Model_Abstract
{
    const DISCONNECT_CONNECTION_ACTION = "connection_disconnect";

    /**
     * Constructor call to set resource model
     */
    public function __construct()
    {
        parent::__construct();

        $this->_setResourceModel('core/config_data');
    }

    /**
     * Gatering connection details form different config fields
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $config = Mage::getModel('varien/object')->setData($this->getData());
        $this->setData(array('config' => $config));
        $this->setId($this->getConfig()->getConfigId());

        $defaultStoreViewId  = $this->_getDefaultStoreViewId();
        $relatedStoreViewIds = $this->_getRelatedStoreViewIds();

        $this->setDefaultStoreViewId($defaultStoreViewId);
        $this->setRelatedStoreViews($relatedStoreViewIds);
        $this->setStatus(Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE, $defaultStoreViewId));
        $this->setBaseCurrency(Mage::getStoreConfig("currency/options/base", $defaultStoreViewId));
        $this->setTaxDefaultCountry(Mage::getStoreConfig("tax/defaults/country", $defaultStoreViewId));
        $this->setMobileAlias(Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ALIAS, $defaultStoreViewId));
        $this->setShopNumber(Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER, $defaultStoreViewId));
    }

    /**
     * Save object data
     *
     * @return Shopgate_Framework_Model_Shopgate_Connection
     */
    public function save()
    {
        return $this;
    }

    /**
     * Helper method to extract the explicit storeview of the connection
     *
     * @return int
     */
    protected function _getDefaultStoreViewId()
    {
        $storeViewId = null;
        switch ($this->getConfig()->getScope()) {
            case "websites":
                $collection = Mage::getModel('core/config_data')->getCollection()
                                  ->addFieldToFilter('scope', $this->getConfig()->getScope())
                                  ->addFieldToFilter('scope_id', $this->getConfig()->getScopeId())
                                  ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_DEFAULT_STORE);

                if ($collection->getSize()) {
                    $storeViewId = $collection->getFirstItem()->getValue();
                }
                break;
            case "stores":
                $storeViewId = $this->getConfig()->getScopeId();
                break;
        }

        if ($storeViewId) {
            return $storeViewId;
        }

        Mage::throwException('No explicit store view set for the shop with the shopnumber: #' . $this->getConfig()
                                                                                                     ->getValue());
    }

    /**
     * Helper method to fetch all storeviewids of any by the connection affected storeview
     *
     * @return array
     */
    protected function _getRelatedStoreViewIds()
    {
        if (is_null($this->getData('related_store_view_ids'))) {
            if ($this->getConfig()->getScope() == 'stores') {
                return array($this->getConfig()->getScopeId());
            } else if ($this->getConfig()->getScope() == 'websites') {
                $collection = Mage::getModel('core/store')->getCollection()
                                  ->addFieldToFilter('website_id', $this->getConfig()->getScopeId());

                if ($this->getConfig()->getScope() == 'websites') {

                    $otherStoreViewsInUse = Mage::getModel('core/config_data')->getCollection()
                                                ->addFieldToFilter('scope', 'stores')
                                                ->addFieldToFilter('scope_id', array('in' => $collection->getAllIds()))
                                                ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER);

                    foreach ($otherStoreViewsInUse as $config) {
                        $collection
                            ->addFieldToFilter('store_id', array('neq' => $config->getScopeId()));
                    }
                }
                $this->setData('related_store_view_ids', $collection->getAllIds());
            }
        }
        return $this->getData('related_store_view_ids');
    }

    /**
     * Sets the active flag in the core/config_data model
     *
     * @return boolean
     */
    public function activate()
    {
        return $this->_saveConfigFlag(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE, 1);
    }

    /**
     * Sets the active flag in the core/config_data model
     *
     * @return boolean
     */
    public function deactivate()
    {
        return $this->_saveConfigFlag(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE, 0);
    }

    /**
     * Removes all through oauth registration saved config entries for the current model
     *
     * @return boolean
     */
    public function unregister()
    {
        ShopgateLogger::getInstance()->log(
                      "Unregister OAuth Shop Connection with shopnumber# " . $this->getShopnumber(), ShopgateLogger::LOGTYPE_DEBUG
        );

        $result      = Mage::getModel('varien/object');
        $deletedKeys = Mage::getModel('varien/object');

        $errors = array();

        foreach ($this->_getRelatedConfigDataEntries() as $config) {
            $deleted = $config->delete();

            if (!$deleted instanceof Mage_Core_Model_Config_Data) {
                $errors[] = "Could not delete Config Field for '" . $config->getPath() . "' in scope (" . $config->getScope() . "," . $config->getScopeId() . ")";
                continue;
            }

            $deletedKeys->setData($config->getPath(), $deleted);
        }

        $result->setData('erros', $errors);
        $result->setData('deleted_keys', $deletedKeys);

        $cacheResult = Mage::app()->getCacheInstance()->clean(Mage_Core_Model_Config::CACHE_TAG);
        ShopgateLogger::getInstance()->log(
                      ' Config cache cleared with result: ' . ($cacheResult ? '[OK]' : '[ERROR]'),
                      ShopgateLogger::LOGTYPE_DEBUG
        );

        $this->_notifyBackendAboutConnectionDisconnect();

        return $result;
    }

    /**
     * Internal helper to notify shopgate about connection disbanding
     *
     * @return void
     */
    protected function _notifyBackendAboutConnectionDisconnect()
    {
        $data = array(
            'action'     => self::DISCONNECT_CONNECTION_ACTION,
            'subaction'  => 'connection_disconnect',
            'uid'        => Mage::helper('shopgate/install')->getUid(),
            'shopnumber' => $this->getShopnumber()
        );

        try {
            $client = new Zend_Http_Client(Shopgate_Framework_Helper_Install::URL_TO_UPDATE_SHOPGATE);
            $client->setParameterPost($data);
            $client->request(Zend_Http_Client::POST);
        } catch (Exception $e) {
            Mage::log("Shopgate_Framework Message: " . self::URL_TO_UPDATE_SHOPGATE . " could not be reached.", Zend_Log::INFO, 'shopgate.log');
        }
    }

    /**
     * Helper method to get all config entries for a given shopnumber
     *
     * @return Mage_Core_Model_Config_Data_Collection
     */
    protected function _getRelatedConfigDataEntries()
    {
        $config = $this->getConfig();

        $collection = Mage::getModel('core/config_data')
                          ->getCollection()
                          ->addFieldToFilter('path', array('like' => 'shopgate%'))
                          ->addFieldToFilter('scope', array('eq' => $config->getScope()))
                          ->addFieldToFilter('scope_id', array('eq' => $config->getScopeId()));

        if ($config->getScope() == 'websites' && Mage::helper('shopgate/config')
                                                     ->getShopConnectionsInWebsiteScope($config->getScopeId(), $this->getShopnumber())
        ) {
            $collection->addFieldToFilter('path', array(
                'nin' => array(
                    Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE,
                    Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_CUSTOMER_NUMBER,
                    Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_API_KEY
                )
            ));
        }

        return $collection->getItems();
    }

    /**
     * Helper method to save core/config_data fields to the database related to a given shopnumber
     *
     * @param string $path
     * @param mixed  $value
     * @return boolean
     */
    protected function _saveConfigFlag($path, $value)
    {
        $collection = Mage::getModel('core/config_data')->getCollection()
                          ->addFieldToFilter('scope', $this->getConfig()->getScope())
                          ->addFieldToFilter('scope_id', $this->getConfig()->getScopeId())
                          ->addFieldToFilter('path', $path);

        if ($collection->getSize()) {
            $config = $collection->getFirstItem();

            if (Mage::getStoreConfig($path, $this->getDefaultStoreViewId()) == (string)$value) {
                return false;
            }

            /* check if a sub scope has an alternating value and correct this instead */
            if (Mage::getStoreConfig($path, $this->getDefaultStoreViewId()) != $config->getValue()) {
                Mage::getConfig()->saveConfig($path, $value, "stores", $this->getDefaultStoreViewId());

                return true;
            }

            $config->setValue($value)->save();

            return true;
        }
    }

    /**
     * Getter method for the oAuth
     *
     * @return string $oAuth
     */
    public function getOauth()
    {
        return $this->getConfig()->getValue();
    }

    /**
     * Loads a shopgate connection by an storeViewId
     *
     * @param int $storeViewId
     * @return Shopgate_Framework_Model_Shopgate_Connection
     */
    public function loadByStoreViewId($storeViewId)
    {
        $collection = Mage::getModel('core/config_data')
                          ->getCollection()
                          ->addFieldToFilter('path', array('eq' => Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER))
                          ->addFieldToFilter('scope', array('eq' => 'stores'))
                          ->addFieldToFilter('scope_id', array('eq' => $storeViewId));

        if ($collection->count()) {
            return $this->load($collection->getFirstItem()->getId());
        }

        $collection = Mage::getModel('core/config_data')
                          ->getCollection()
                          ->addFieldToFilter('path', array('eq' => Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_DEFAULT_STORE))
                          ->addFieldToFilter('scope', array('eq' => 'websites'))
                          ->addFieldToFilter('value', array('eq' => $storeViewId));

        if ($collection->count()) {
            return $this->load($collection->getFirstItem()->getId());
        }

        return null;
    }
}