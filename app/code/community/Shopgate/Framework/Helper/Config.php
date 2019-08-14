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
 * User: Peter Liebig
 * Date: 22.01.14
 * Time: 15:06
 * E-Mail: p.liebig@me.com
 */

/**
 * config helper
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Helper_Config extends Mage_Core_Helper_Abstract
{
    /**
     * const for community
     */
    const COMMUNITY_EDITION = 'Community';

    /**
     * const for community
     */
    const ENTERPRISE_EDITION = 'Enterprise';

    /**
     * @var string
     */
    protected $_magentoVersion15 = '';

    /**
     * @var string
     */
    protected $_magentoVersion1410 = '';

    /**
     * @var Shopgate_Framework_Model_Config
     */
    protected $_config = null;

    /**
     * construct for helper
     */
    public function __construct()
    {
        $this->_magentoVersion15   = ($this->getEdition() == 'Enterprise') ? '1.9.1.0' : '1.5';
        $this->_magentoVersion1410 = ($this->getEdition() == 'Enterprise') ? '1.9.0.0' : '1.4.1.0';
    }

    /**
     * get edition of magento
     *
     * @return string
     */
    public function getEdition()
    {
        $edition = self::COMMUNITY_EDITION;

        if (method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
        } else {
            $dir = mage::getBaseDir('code') . DS . 'core' . DS . self::ENTERPRISE_EDITION;
            if (file_exists($dir)) {
                $edition = self::ENTERPRISE_EDITION;
            }
        }

        return $edition;
    }

    /**
     * Checks if current magento ver. is lower than param
     *
     * @param string $version - magento version e.g "1.9.1.1"
     * @return bool
     */
    public function getIsMagentoVersionLower($version)
    {
        return version_compare(Mage::getVersion(), $version, "<");
    }

    /**
     * compare version if it is lower than 1.5
     *
     * @return mixed
     */
    public function getIsMagentoVersionLower15()
    {
        return $this->getIsMagentoVersionLower($this->_magentoVersion15);
    }

    /**
     * compare version if it is lower than 1.4.1.0
     *
     * @return mixed
     */
    public function getIsMagentoVersionLower1410()
    {
        return $this->getIsMagentoVersionLower($this->_magentoVersion1410);
    }

    /**
     * @param int $storeId
     * @return Shopgate_Framework_Model_Config
     */
    public function getConfig($storeId = null)
    {
        if (!$this->_config) {
            $this->_config = Mage::getModel('shopgate/config');
            $this->_config->loadConfig($storeId);
        }
        return $this->_config;
    }

    /**
     * checks if a shopnumber is already registered or a storeview has already a shopnumber set explicit
     *
     * @param int $shopnumber
     * @param int $storeViewId
     * @return string
     */
    public function isOAuthShopAlreadyRegistered($shopnumber, $storeViewId)
    {
        /* has shopnumber defined in same website scope */
        if (Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER)
                ->addFieldToFilter('value', $shopnumber)
                ->count()
        ) {
            return true;
        }

        /* a shopnumber is set on store view scope with the same scope_id as the base store view for the new shopnumber */
        if (Mage::getModel('core/config_data')->getCollection()
                ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER)
                ->addFieldToFilter('scope', 'stores')
                ->addFieldToFilter('scope_id', $storeViewId)
                ->addFieldToFilter('value', array('nin' => array('', null)))
                ->count()
        ) {
            return true;
        }

        /* a shopnumber has a default store view set exactly like the base store view for the new shopnumber */
        $collection = Mage::getModel('core/config_data')->getCollection()
                          ->addFieldToFilter(
                              '`main_table`.`path`',
                              Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER
                          )
                          ->addFieldToFilter('`main_table`.`scope`', 'websites')
                          ->addFieldToFilter('`main_table`.`value`', array('nin' => array('', null)))
                          ->addFieldToFilter(
                              '`dsv`.`path`',
                              Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_DEFAULT_STORE
                          )
                          ->getSelect()
                          ->joinInner(
                              array('dsv' => 'core_config_data'),
                              '`dsv`.`scope` = `main_table`.`scope` AND `dsv`.`scope_id` = `main_table`.`scope_id`',
                              array('default_store_view' => 'value')
                          )->query()
                          ->fetchAll();

        foreach ($collection as $item) {
            if (isset($item['default_store_view'])
                && $item['default_store_view'] == $storeViewId
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * fetches any shopnumber definition in a given website scope
     *
     * @param int $websiteId
     * @param int $shopnumber
     * @return int[] $shopnumbers
     */
    public function getShopConnectionsInWebsiteScope($websiteId, $shopnumber)
    {
        $relatedStoreViews = Mage::getModel("core/store")
                                 ->getCollection()
                                 ->addFieldToFilter('code', array('neq' => 'admin'))
                                 ->addFieldToFilter('website_id', array('eq' => $websiteId))
                                 ->getAllIds();

        $shopnumbers = Mage::getModel("core/config_data")
                           ->getCollection()
                           ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER)
                           ->addFieldToFilter('scope', 'stores')
                           ->addFieldToFilter('scope_id', array('in' => $relatedStoreViews))
                           ->addFieldToFilter('value', array('neq' => array('')));

        return $shopnumbers->getSize();
    }

    /**
     * Fetches any defined shopgate connection
     *
     * @return int
     */
    public function getShopgateConnections()
    {
        return Mage::getModel("core/config_data")
                   ->getCollection()
                   ->addFieldToFilter('path', Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_SHOP_NUMBER)
                   ->addFieldToFilter('value', array('neq' => array('')));
    }

    /**
     * Checks if any shopgate connections persist already
     *
     * @return int
     */
    public function hasShopgateConnections()
    {
        return $this->getShopgateConnections()
                    ->getSize();
    }

    /**
     * Gets the system_config/edit url with the proper scope set
     *
     * @param $connectionIds array
     * @return string
     */
    public function getConfigureUrl($connectionIds)
    {
        $connection = Mage::getModel('shopgate/shopgate_connection')->load($connectionIds);

        $scope   = $connection->getConfig()->getScope();
        $scopeId = $connection->getConfig()->getScopeId();

        $scopeModelType = substr($scope, 0, -1);

        $scopeCode = Mage::getModel('core/' . $scopeModelType)->load($scopeId)->getCode();

        return Mage::helper('adminhtml')
                   ->getUrl('*/system_config/edit/section/shopgate/' . $scopeModelType . '/' . $scopeCode);
    }

    /**
     * Provide website code and get Shopgate
     * store id OR magento's default store id
     *
     * @param $websiteCode
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getStoreIdByWebsite($websiteCode)
    {
        $storeId = Mage::app()
                       ->getWebsite($websiteCode)
                       ->getConfig('shopgate/option/default_store');
        if (!$storeId) { //use magento default store
            $storeId = Mage::app()
                           ->getWebsite($websiteCode)
                           ->getDefaultGroup()
                           ->getDefaultStoreId();
        }
        return $storeId;
    }

    /**
     * @param $storeCode
     * @return int
     */
    public function getStoreIdByStoreCode($storeCode)
    {
        $storeId = Mage::getModel('core/store')
                       ->loadConfig($storeCode)
                       ->getId();
        if (!$storeId) {
            $storeId = Mage::getModel('core/store')
                           ->load($storeCode, 'code')
                           ->getId();
        }
        return $storeId;
    }

    /**
     * @param null $storeId
     * @return mixed - oauth if it exists
     */
    public function getOauthToken($storeId = null)
    {
        return Mage::getStoreConfig(
            Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_OAUTH_ACCESS_TOKEN,
            $storeId
        );
    }
}
