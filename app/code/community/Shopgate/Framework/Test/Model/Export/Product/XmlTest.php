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
 * @author             Shopgate GmbH <interfaces@shopgate.com>
 * @coversDefaultClass Shopgate_Framework_Model_Export_Product_Xml
 */
class Shopgate_Framework_Test_Model_Export_Product_XmlTest extends PHPUnit_Framework_TestCase
{
    /** @var Shopgate_Framework_Model_Export_Product_Xml $class */
    protected $class;
    /** @var Shopgate_Framework_Test_Model_ProductUtility */
    protected $utility;

    /**
     * Set up of product creation utility and class to test against
     */
    public function setUp()
    {
        $this->class   = Mage::getModel('shopgate/export_product_xml');
        $this->utility = new Shopgate_Framework_Test_Model_ProductUtility();
    }

    /**
     * Tests the standard product group price export
     * 
     * @uses Shopgate_Framework_Test_Model_ProductUtility
     * @uses Shopgate_Model_Catalog_Price
     * @uses ReflectionClass
     * 
     * @covers ::_createGroupPriceNode
     * @covers ::_calculateCatalogGroupPriceRules
     * @covers ::_adjustGroupPrice
     */
    public function testGroupPriceNodeNoRules()
    {
        $customerGroup = 3;
        $groupDiscount = 10.00;
        $salePrice     = 100;
        $final         = $salePrice - $groupDiscount;
        $product       = $this->utility->createSimpleProduct();

        $product->setData(
            'group_price', array(
                             array(
                                 'website_id'    => 0,
                                 'cust_group'    => $customerGroup,
                                 'website_price' => $groupDiscount,
                             )
                         )
        );
        $product->save();
        $this->class->setItem($product);
        $this->utility->updatePriceIndexTable($product, $salePrice, $final, $customerGroup);
        $priceModel = new Shopgate_Model_Catalog_Price();
        $priceModel->setSalePrice($salePrice);

        $reflection = new ReflectionClass($this->class);
        $method     = $reflection->getMethod('_createGroupPriceNode');
        $method->setAccessible(true);
        $method->invoke($this->class, $priceModel);

        /** @var Shopgate_Model_Catalog_TierPrice[] $tier */
        $tier = $priceModel->getTierPricesGroup();

        $this->assertEquals($groupDiscount, $tier[0]->getData('reduction'));
    }

    /**
     * Remove data entries
     */
    public function tearDown()
    {
        $this->utility->deleteProducts();
    }
}
