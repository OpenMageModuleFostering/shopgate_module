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
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: pliebig
 * Date: 18.03.14
 * Time: 19:17
 * E-Mail: p.liebig@me.com
 */

/**
 * category export model used for csv certainly
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Category extends Shopgate_Model_Catalog_Category
{
    /**
     * @var null
     */
    protected $_parentId = null;

    /**
     * @param $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->_parentId = $parentId;

        return $this;
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return mixed
     */
    public function getImageUrl($category)
    {
        $url = null;
        if ($image = $category->getThumbnail()) {
            $url = Mage::getBaseUrl('media') . 'catalog/category/' . $image;
        }
        if (!$url) {
            $url = $category->getImageUrl();
        }
        return $this->_getExportHelper()->parseUrl($url);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @return mixed
     */
    public function getDeepLinkUrl($category)
    {
        return $this->_getExportHelper()->parseUrl($category->getUrl());
    }

    /**
     * @return Shopgate_Framework_Helper_Export
     */
    protected function _getExportHelper()
    {
        return Mage::helper('shopgate/export');
    }

    /**
     * @return Shopgate_Framework_Model_Config
     */
    protected function _getConfig()
    {
        return $this->_getHelper()->getConfig();
    }

    /**
     * return customer helper
     *
     * @return Shopgate_Framework_Helper_Customer
     */
    protected function _getCustomerHelper()
    {
        return Mage::helper('shopgate/customer');
    }

    /**
     * return default data helper
     *
     * @return Shopgate_Framework_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate');
    }

    /**
     * return config helper
     *
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('shopgate/config');
    }
}
