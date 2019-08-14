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
 * Product.php
 *
 * @category    Shopgate
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 * @copyright   2014 Shopgate GmbH
 * @version     2.4.11
 * @since       29.01.14
 */
class Shopgate_Framework_Model_Resource_Product
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product
{
    /**
     * Retrieve product category identifiers with position and max_position from category.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getCategoryIdsAndPosition($product)
    {
        $select = $this->_getReadAdapter()->select()
                       ->from(
                       array('c' => $this->_productCategoryTable),
                       array('category_id', 'position')
            )->joinLeft(
                       array('c2' => $this->_productCategoryTable),
                       'c.category_id = c2.category_id',
                       array('max_position' => new Zend_Db_Expr('max(c2.position)'))
            )->where('c.product_id=?', $product->getId())
                       ->group('c.category_id');

		$catAndPos = $this->_getReadAdapter()->fetchAll($select);
		$result    = array();

		foreach ($catAndPos as $cat) {
			$result[$cat['category_id']] = $cat;
			/** @var Mage_Catalog_Model_Category $category */
			$category = Mage::getModel('catalog/category')->load($cat['category_id']);
			$anchors  = $category->getAnchorsAbove();
			foreach ($anchors as $anchor) {
				if (array_key_exists($anchor, $result)) {
					continue;
				}
				$result[$anchor] = array(
					'category_id'  => $anchor,
					'position'     => $cat['max_position'],
					'max_position' => $cat['max_position']
				);
			}
		}

		return $result;
    }
}
