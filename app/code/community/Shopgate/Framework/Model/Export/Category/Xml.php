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
class Shopgate_Framework_Model_Export_Category_Xml extends Shopgate_Framework_Model_Export_Category
{
    /** @var  Mage_Catalog_Model_Category */
    protected $item;
    /** @var int | null */
    protected $_maxPosition = null;

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setMaximumPosition($position)
    {
        $this->_maxPosition = $position;
        return $this;
    }

    /**
     * @return null | int
     */
    public function getMaximumPosition()
    {
        return $this->_maxPosition;
    }

    /**
     * Generate data dom object
     *
     * @return $this
     */
    public function generateData()
    {
        foreach ($this->fireMethods as $method) {
            $this->{$method}($this->item);
        }

        return $this;
    }

    /**
     * Set category id
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * Set category sort order
     */
    public function setSortOrder()
    {
        parent::setSortOrder($this->getMaximumPosition() - $this->item->getData('position'));
    }

    /**
     * Set category name
     */
    public function setName()
    {
        parent::setName($this->item->getName());
    }

    /**
     * Set parent category id
     */
    public function setParentUid()
    {
        parent::setParentUid($this->item->getParentId() != $this->_parentId ? $this->item->getParentId() : null);
    }

    /**
     * Category link in shop
     */
    public function setDeeplink()
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * Check if category is anchor
     */
    public function setIsAnchor()
    {
        parent::setIsAnchor($this->item->getData('is_anchor'));
    }

    /**
     * Set category image
     */
    public function setImage()
    {
        if ($this->item->getImageUrl()) {
            $imageItem = new Shopgate_Model_Media_Image();

            $imageItem->setUid(1);
            $imageItem->setSortOrder(1);
            $imageItem->setUrl($this->getImageUrl($this->item));
            $imageItem->setTitle($this->item->getName());

            parent::setImage($imageItem);
        }
    }

    /**
     * Set active state
     */
    public function setIsActive()
    {
        $catIds      = Mage::getStoreConfig(
            Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_HIDDEN_CATEGORIES
        );
        $catIdsArray = array();

        if (!empty($cat_ids)) {
            $catIdsArray = explode(",", $catIds);
            foreach ($catIdsArray as &$catId) {
                $catId = trim($catId);
            }
        }

        $isActive = $this->item->getData('is_active');
        if (in_array($this->item->getId(), $catIdsArray)
            || array_intersect(
                $catIdsArray,
                $this->item->getParentIds()
            )
        ) {
            $isActive = 1;
        }

        if (Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_EXPORT_NAVIGATION_CATEGORIES_ONLY)
            && !$this->item->getData('include_in_menu')
        ) {
            $isActive = 0;
        }

        parent::setIsActive($isActive);
    }
}