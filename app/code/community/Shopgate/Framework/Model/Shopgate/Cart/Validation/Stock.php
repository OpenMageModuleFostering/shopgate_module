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
 * Model to validate StockItem on checkCart
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Shopgate_Cart_Validation_Stock extends Mage_Core_Model_Abstract
{
    /**
     * Validate stock of a quoteItem
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param float                       $priceInclTax
     * @param float                       $priceExclTax
     * @return ShopgateCartItem $result
     */
    public function validateStock(Mage_Sales_Model_Quote_Item $item, $priceInclTax, $priceExclTax)
    {
        switch ($item->getProduct()->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                $model = Mage::getModel('shopgate/shopgate_cart_validation_stock_bundle');
                break;
            default:
                $model = Mage::getModel('shopgate/shopgate_cart_validation_stock_simple');
        }

        return $model->validateStock($item, $priceInclTax, $priceExclTax);
    }
}
