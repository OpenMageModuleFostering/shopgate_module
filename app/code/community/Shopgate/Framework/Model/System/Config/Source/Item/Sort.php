<?php
/**
 * Shopgate GmbH
 * URHEBERRECHTSHINWEIS
 * Dieses Plugin ist urheberrechtlich geschützt. Es darf ausschließlich von Kunden der Shopgate GmbH
 * zum Zwecke der eigenen Kommunikation zwischen dem IT-System des Kunden mit dem IT-System der
 * Shopgate GmbH über www.shopgate.com verwendet werden. Eine darüber hinausgehende Vervielfältigung, Verbreitung,
 * öffentliche Zugänglichmachung, Bearbeitung oder Weitergabe an Dritte ist nur mit unserer vorherigen
 * schriftlichen Zustimmung zulässig. Die Regelungen der §§ 69 d Abs. 2, 3 und 69 e UrhG bleiben hiervon unberührt.
 * COPYRIGHT NOTICE
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
 * Date: 06.02.14
 * Time: 10:09
 * E-Mail: p.liebig@me.com
 */

/**
 * Items Sort system config source model
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_System_Config_Source_Item_Sort
{

    /**
     * default sorting type -> price_asc
     */
    const SORT_TYPE_DEFAULT = 'default';

    /**
     * sort products by price descending
     */
    const SORT_TYPE_PRICE_DESC = 'price_desc';

    /**
     * sort products by their position in the category
     */
    const SORT_TYPE_POSITION = 'position';

    /**
     * sort products by created_at date
     */
    const SORT_TYPE_NEWEST = 'newest';

    /**
     * sort products by updated_at date
     */
    const SORT_TYPE_LAST_UPDATED = 'updated';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::SORT_TYPE_DEFAULT, 'label' => Mage::helper('shopgate')->__('Default')),
            array('value' => self::SORT_TYPE_PRICE_DESC, 'label' => Mage::helper('shopgate')->__('Price, descending')),
            array('value' => self::SORT_TYPE_POSITION, 'label' => Mage::helper('shopgate')->__('Category Position')),
            array('value' => self::SORT_TYPE_LAST_UPDATED, 'label' => Mage::helper('shopgate')->__('Last updated first')),
            array('value' => self::SORT_TYPE_NEWEST, 'label' => Mage::helper('shopgate')->__('Product creation date descending'))
        );
    }
}
