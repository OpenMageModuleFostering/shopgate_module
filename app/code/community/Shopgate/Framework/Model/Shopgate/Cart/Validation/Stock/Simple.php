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
 * Model to validate StockItem for generic products on checkCart
 *
 * @category   Shopgate
 * @package    Shopgate_Framework
 * @author     Shopgate GmbH <steffen.meuser@shopgate.com>
 */
class Shopgate_Framework_Model_Shopgate_Cart_Validation_Stock_Simple
    extends Shopgate_Framework_Model_Shopgate_Cart_Validation_Stock
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
        /** @var Mage_Catalog_Model_Product $product */
        $product = $item->getProduct();
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = $product->getStockItem();
        $isBuyable = true;

        if ($product->isConfigurable()) {
            $parent  = $product;
            $product = $product->getCustomOption('simple_product')->getProduct();
            $product->setShopgateItemNumber($parent->getShopgateItemNumber());
            $product->setShopgateOptions($parent->getShopgateOptions());
            $product->setShopgateInputs($parent->getShopgateInputs());
            $product->setShhopgateAttributes($parent->getShhopgateAttributes());
            $stockItem = $item->getProduct()->getCustomOption('simple_product')->getProduct()->getStockItem();
        }

        if (null == $product->getShopgateItemNumber()) {
            $product->setShopgateItemNumber($product->getId());
        }

        $errors = array();

        if (Mage::helper('shopgate/config')->getIsMagentoVersionLower1410()) {
            $checkIncrements = Mage::helper('shopgate')->checkQtyIncrements($stockItem, $item->getQty());
        } else {
            $checkIncrements = $stockItem->checkQtyIncrements($item->getQty());
        }

        if ($stockItem->getManageStock() && !$product->isSaleable() && !$stockItem->getBackorders()) {
            $isBuyable        = false;
            $error            = array();
            $error['type']    = ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK;
            $error['message'] = ShopgateLibraryException::getMessageFor(
                                                        ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK
            );
            $errors[]         = $error;
        } else {
            if ($stockItem->getManageStock() && !$stockItem->checkQty($item->getQty()) && !$stockItem->getBackorders()
            ) {
                $isBuyable        = false;
                $error            = array();
                $error['type']    = ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE;
                $error['message'] = ShopgateLibraryException::getMessageFor(
                                                            ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE
                );
                $errors[]         = $error;
            } else {
                if ($stockItem->getManageStock() && $checkIncrements->getHasError()) {
                    $isBuyable        = false;
                    $error            = array();
                    $error['type']    = ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE;
                    $error['message'] = ShopgateLibraryException::getMessageFor(
                                                                ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE
                    );
                    $errors[]         = $error;
                    $stockItem->setQty(
                              (int)($item->getQtyToAdd() / $stockItem->getQtyIncrements()) * $stockItem->getQtyIncrements()
                    );
                }
            }
        }
        $qtyBuyable = $isBuyable ? (int)$item->getQty() : (int)$stockItem->getQty();

        return Mage::helper('shopgate')->generateShopgateCartItem(
                   $product,
                   $isBuyable,
                   $qtyBuyable,
                   $priceInclTax,
                   $priceExclTax,
                   $errors,
                   (int)$stockItem->getQty()
        );
    }
}
