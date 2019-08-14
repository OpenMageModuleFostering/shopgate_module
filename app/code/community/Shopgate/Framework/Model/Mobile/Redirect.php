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
 * Date: 24.01.14
 * Time: 18:04
 * E-Mail: p.liebig@me.com
 */

/**
 * mobile redirect model
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Mobile_Redirect extends Mage_Core_Model_Abstract
{

    /**
     * Caching const
     */
    const CACHE_PRODUCT_OBJECT_ID = 'shopgate_mobile_redirect_product';
    const CACHE_PAGE_IDENTIFIER   = 'shopgate_mobile_redirect_category';

    /**
     * Type const
     */
    const CATEGORY = 'category';
    const PRODUCT  = 'product';
    const PAGE     = 'page';
    const SEARCH   = 'result';
    const INDEX    = 'index';

    /**
     * @var Shopgate_Framework_Model_Config
     */
    protected $_config;

    /**
     * Construct and define config
     */
    public function _construct()
    {
        parent::_construct();
        $this->_config = Mage::helper('shopgate/config')->getConfig();
    }

    /**
     * Redirect with 301
     */
    public function redirectWithCode()
    {
        try {
            // no redirection in admin
            if (Mage::app()->getStore()->isAdmin()) {
                Mage::getSingleton('core/session')->setData('shopgate_header', '');
                return;
            }

            // isAjax is not available on Magento < 1.5 >> no ajax-check
            if (method_exists(Mage::app()->getRequest(), 'isAjax')
                && Mage::app()->getRequest()->isAjax()
            ) {
                Mage::getSingleton('core/session')->setData('shopgate_header', '');
                return;
            }

            if (!$this->_config->isValidConfig()) {
                Mage::getSingleton('core/session')->setData('shopgate_header', '');
                return;
            }

            if (!Mage::getStoreConfig(
                Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE,
                $this->_config->getStoreViewId()
            )
            ) {
                Mage::getSingleton('core/session')->setData('shopgate_header', '');
                return;
            }

            $jsHeader = $this->_getJsHeader();

            Mage::getSingleton('core/session')->setData('shopgate_header', $jsHeader);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->setData('shopgate_header', '');
            ShopgateLogger::getInstance()->log('error in mobile redirector: ' . $e->getMessage());
        }
    }

    /**
     * Get the js redirection header code
     *
     * @return string
     */
    protected function _getJsHeader()
    {
        $objId  = Mage::app()->getRequest()->getParam('id');
        $action = Mage::app()->getRequest()->getControllerName();

        $baseUrl    = trim(Mage::app()->getRequest()->getBaseUrl(), '/');
        $requestUrl = trim(Mage::app()->getRequest()->getRequestUri(), '/');

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_STORES)
            && $action == 'index' && $baseUrl != $requestUrl
        ) {
            $action = 'category';
            $objId  = Mage::app()->getStore()->getRootCategoryId();
        }

        $redirectType      = Mage::getStoreConfig(
            Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_REDIRECT_TYPE,
            $this->_config->getStoreViewId()
        );
        $automaticRedirect = $redirectType == Shopgate_Framework_Model_Config::REDIRECTTYPE_HTTP ? true : false;

        switch ($action) {
            case self::PRODUCT:
                $jsHeader = $this->_getCachedJsHeaderByType(self::PRODUCT, $objId, $automaticRedirect);
                break;
            case self::CATEGORY:
                $jsHeader = $this->_getCachedJsHeaderByType(self::CATEGORY, $objId, $automaticRedirect);
                break;
            case self::PAGE:
                $objId          = Mage::app()->getRequest()->getParam('page_id');
                $pageIdentifier = $this->_getPageIdentifier($objId);
                $jsHeader       = $this->_getCachedJsHeaderByType(self::PAGE, $pageIdentifier, $automaticRedirect);
                break;
            case self::SEARCH:
                $search   = Mage::app()->getRequest()->getParam('q');
                $jsHeader = $this->_getCachedJsHeaderByType(self::SEARCH, $search, $automaticRedirect);
                break;
            case self::INDEX:
                $jsHeader = $this->_getCachedJsHeaderByType(self::INDEX, null, $automaticRedirect);
                break;
            default:
                $jsHeader = $this->_getCachedJsHeaderByType(null, null, $automaticRedirect);
                break;
        }

        return $jsHeader;
    }

    /**
     * Determine the (cached) page identifier
     *
     * @param string $pageId
     * @return string
     */
    protected function _getPageIdentifier($pageId)
    {
        $cacheKey  = md5(
            implode(
                '-',
                array(
                    self::CACHE_PAGE_IDENTIFIER,
                    $pageId,
                    $this->_config->getStoreViewId()
                )
            )
        );
        $cacheData = Mage::app()->loadCache($cacheKey);
        if ($cacheData) {
            return unserialize($cacheData);
        }

        $page   = Mage::getModel('cms/page')->load($pageId);
        $result = $page->getIdentifier();
        Mage::app()->saveCache(
            serialize($result),
            $cacheKey,
            array(
                Mage_Cms_Model_Page::CACHE_TAG . '_' . $pageId,
                Mage_Core_Model_Mysql4_Collection_Abstract::CACHE_TAG
            )
        );

        return $result;
    }

    /**
     * Get cached header js for redirect or load and save to cache
     *
     * @param $type  string
     * @param $objId string|int
     * @param $automaticRedirect
     * @return mixed|string|void
     */
    protected function _getCachedJsHeaderByType($type, $objId, $automaticRedirect)
    {
        $storeViewId = $this->_config->getStoreViewId();
        switch ($type) {
            case self::CATEGORY:
                $cacheKey = $storeViewId . '_sg_mobile_category_' . $objId . '_redirect_type_' . intval(
                        $automaticRedirect
                    );
                break;
            case self::PRODUCT:
                $cacheKey = $storeViewId . '_sg_mobile_item_' . $objId . '_redirect_type_' . intval($automaticRedirect);
                break;
            case self::PAGE:
                $cacheKey = $storeViewId . '_sg_mobile_page_' . $objId . '_redirect_type_' . intval($automaticRedirect);
                break;
            case self::SEARCH:
                $cacheKey = $storeViewId . '_sg_mobile_catalogsearch_' . md5($objId) . '_redirect_type_' . intval(
                        $automaticRedirect
                    );
                break;
            case self::INDEX:
                $cacheKey = $storeViewId . '_sg_mobile_index_redirect_type_' . intval($automaticRedirect);
                break;
            default:
                $cacheKey = $storeViewId . '_sg_mobile_default_type_' . intval($automaticRedirect);
                break;
        }

        $cache = Mage::app()->getCacheInstance();
        $value = $cache->load($cacheKey);

        if ($value !== false) {
            $jsHeader = unserialize($value);
        } else {
            $shopgateRedirect = $this->_createMobileRedirect($type, $objId);

            if (!in_array(
                    $type,
                    array(
                        self::CATEGORY,
                        self::PRODUCT,
                        self::SEARCH,
                        self::INDEX
                    )
                )
                && !Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ENABLE_DEFAULT_REDIRECT)
            ) {
                $shopgateRedirect->suppressRedirect();
            }

            $disabledRoutes = explode(
                ',',
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_ROUTES)
            );
            $route          = Mage::app()->getRequest()->getRouteName();
            if (in_array($route, $disabledRoutes)) {
                $shopgateRedirect->suppressRedirect();
            }

            $disabledControllers = explode(
                ',',
                Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_CONTROLLERS)
            );
            $controllerName      = $type;
            if (in_array($controllerName, $disabledControllers)) {
                $shopgateRedirect->suppressRedirect();
            }

            if ($controllerName == 'product') {
                $productId        = Mage::app()->getRequest()->getParam('id');
                $disabledProducts = explode(
                    ',',
                    Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_PRODUCTS)
                );

                if (in_array($productId, $disabledProducts)) {
                    $shopgateRedirect->suppressRedirect();
                }
            }

            if ($controllerName == 'category') {
                $categoryId         = Mage::app()->getRequest()->getParam('id');
                $disabledCategories = explode(
                    ',',
                    Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_DISABLE_REDIRECT_CATEGORIES)
                );
                if (in_array($categoryId, $disabledCategories)) {
                    $shopgateRedirect->suppressRedirect();
                }
            }

            switch ($type) {
                case self::CATEGORY:
                    $jsHeader = $shopgateRedirect->buildScriptCategory($objId, $automaticRedirect);
                    break;
                case self::PRODUCT:
                    $jsHeader = $shopgateRedirect->buildScriptItem($objId, $automaticRedirect);
                    break;
                case self::PAGE:
                    $jsHeader = $shopgateRedirect->buildScriptCms($objId, $automaticRedirect);
                    break;
                case self::SEARCH:
                    $jsHeader = $shopgateRedirect->buildScriptSearch($objId, $automaticRedirect);
                    break;
                case self::INDEX:
                    $jsHeader = $shopgateRedirect->buildScriptShop($automaticRedirect);
                    break;
                default:
                    $jsHeader = $shopgateRedirect->buildScriptDefault($automaticRedirect);
                    break;
            }

            $cache->save(
                serialize($jsHeader),
                $cacheKey,
                array(
                    'shopgate_mobile_redirect',
                    Mage_Core_Model_Layout_Update::LAYOUT_GENERAL_CACHE_TAG
                ),
                7200
            );
        }
        return $jsHeader;
    }



    /**
     * Get cached header js for redirect or load and save to cache
     *
     * @param $type  string
     * @param $objId string|int
     * @param $automaticRedirect
     * @return mixed|string|void
     */
    protected function _createMobileRedirect($type, $objId)
    {
        $builder     = new ShopgateBuilder($this->_config);
        $redirectObj = $builder->buildMobileRedirect($_SERVER['HTTP_USER_AGENT'], $_GET, $_COOKIE);

        try {
            $storeId   = $this->_config->getStoreViewId();
            $siteName  = Mage::app()->getWebsite()->getName();
            $mobileUrl = $this->_config->getCname();
            $shopUrl   = Mage::getStoreConfig("web/unsecure/base_url", $storeId);

            switch ($type) {
                case self::CATEGORY:
                    $pageTitle = Mage::getModel('catalog/category')->setStoreId($storeId)->load($objId)->getName();
                    break;
                case self::PRODUCT:
                    $pageTitle = Mage::getModel('catalog/product')->setStoreId($storeId)->load($objId)->getName();
                    break;
                case self::PAGE:
                    $pageTitle = Mage::getModel('cms/page')->setStoreId($storeId)->load($objId)->getTitle();
                    break;
                default:
                    $pageTitle = $siteName;
                    break;
            }
            if (empty($pageTitle)) {
                $pageTitle = $siteName;
            }

            empty($siteName)  ?: $redirectObj->addSiteParameter(
                Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_SITENAME, $siteName);
            empty($shopUrl)   ?: $redirectObj->addSiteParameter(
                Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_DESKTOP_URL, $shopUrl);
            empty($mobileUrl) ?: $redirectObj->addSiteParameter(
                Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_MOBILE_WEB_URL, $mobileUrl);
            empty($pageTitle) ?: $redirectObj->addSiteParameter(
                Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_TITLE, $pageTitle);

            if ($type == self::PRODUCT) {

                /** @var Mage_Catalog_Model_Product $product */
                $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($objId);

                $categoryId = Mage::app()->getRequest()->getParam('category');
                if (!empty($categoryId)) {
                    $categoryName = Mage::getModel('catalog/category')->load($categoryId)->getName();
                }

                $name          = $product->getData('name');
                $availableText = $product->isInStock() ? 'instock' : 'oos';

                $eanAttCode = Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EAN_ATTR_CODE, $storeId);
                if (is_object($eanAttCode)) {
                    $ean = $product->getData($eanAttCode);
                }

                $image = $product->getMediaGalleryImages()->getFirstItem();
                if (is_object($image)) {
                    $imageUrl = $image->getData('url');
                }

                $description = $product->getData('short_description');
                if (strlen($description) > 140) {
                    $description = substr($description, 0, 136) . ' ...';
                }

                $price           = $product->getData('price');
                $defaultCurrency = Mage::getStoreConfig("currency/options/default", $storeId);
                $baseCurrency    = Mage::getStoreConfig("currency/options/base", $storeId);
                if ($defaultCurrency != $baseCurrency) {
                    $price = Mage::helper('directory')->currencyConvert($price, $baseCurrency, $defaultCurrency);
                }
                $priceIsGross = Mage::getStoreConfig("tax/calculation/price_includes_tax", $storeId);
                $request = new Varien_Object(
                    array(
                        'country_id'        => Mage::getStoreConfig("tax/defaults/country", $storeId),
                        'region_id'         => Mage::getStoreConfig("tax/defaults/region", $storeId),
                        'postcode'          => Mage::getStoreConfig("tax/defaults/postcode", $storeId),
                        'customer_class_id' => Mage::getModel("tax/calculation")->getDefaultCustomerTaxClass($storeId),
                        'product_class_id'  => $product->getTaxClassId(),
                        'store'             => Mage::app()->getStore($storeId)
                    )
                );

                /** @var Mage_Tax_Model_Calculation $model */
                $taxRate = Mage::getSingleton('tax/calculation')->getRate($request) / 100;
                if ($priceIsGross) {
                    $priceNet   = round($price / (1 + $taxRate), 2);
                    $priceGross = round($price, 2);
                } else {
                    $priceNet   = round($price, 2);
                    $priceGross = round($price * (1 + $taxRate), 2);
                }

                empty($imageUrl) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_IMAGE, $imageUrl);
                empty($name) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_NAME, $name);
                empty($description) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_DESCRIPTION_SHORT, $description);
                empty($ean) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_EAN, $ean);
                empty($availableText) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_AVAILABILITY, $availableText);
                empty($categoryName) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_CATEGORY, $categoryName);
                empty($priceGross) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_PRICE, $priceGross);
                empty($priceGross) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_CURRENCY, $defaultCurrency);
                empty($priceNet) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_PRETAX_PRICE, $priceNet);
                empty($priceNet) ?: $redirectObj->addSiteParameter(
                    Shopgate_Helper_Redirect_TagsGenerator::SITE_PARAMETER_PRODUCT_PRETAX_CURRENCY, $defaultCurrency);

            }
        } catch (Exception $e) {
            ShopgateLogger::getInstance()->log('error on tag creation for type:' . $type . ' object ID:' . $objId);
        }

        return $redirectObj;
    }
}
