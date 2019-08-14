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
 *
 * @coversDefaultClass Shopgate_Framework_Model_Observer
 */
class Shopgate_Framework_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Returns true that we are making a Shopgate API request
     */
    public function setUp()
    {
        $helper = $this->getHelperMock('shopgate', array('isShopgateApiRequest'));
        $helper->expects($this->any())->method('isShopgateApiRequest')->willReturn(true);
        $this->replaceByMock('helper', 'shopgate', $helper);
    }

    /**
     * @param bool $expected      - whether Sales Rules are blocked from applying to cart/order
     * @param bool $config        - config value of "Apply Sales Rules"
     * @param bool $registryValue - registry value to disable sales rules from applying, true - disable
     *
     * @covers ::beforeSalesrulesLoaded
     * @dataProvider salesRuleDataProvider
     */
    public function testBeforeSalesrulesLoaded($expected, $config, $registryValue)
    {
        $observer       = new Varien_Event_Observer();
        $event          = new Varien_Event();
        $collectionMock = $this->getResourceModelMock('salesrule/rule_collection', array('getReadConnection'));
        $event->setData('collection', $collectionMock);
        $observer->setEvent($event);
        $registryValue ? Mage::register('shopgate_disable_sales_rules', $registryValue) : false;

        Mage::app()->getStore(0)->setConfig('shopgate/orders/apply_cart_rules', (int)$config);
        Mage::getModel('shopgate/observer')->beforeSalesrulesLoaded($observer);

        /** @var Mage_SalesRule_Model_Resource_Rule_Collection $collectionMock */
        $where  = $collectionMock->getSelect()->getPart(Zend_Db_Select::WHERE);
        $actual = isset($where[0]) && strpos($where[0], 'coupon_type') !== false;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function salesRuleDataProvider()
    {
        return array(
            array(
                'Sales Rules are blocked'          => true,
                'Apply Sales Rules in cfg'         => true,
                'Sales Rules Disabled in registry' => true
            ),
            array(
                'Sales Rules are blocked'          => false,
                'Apply Sales Rules in cfg'         => true,
                'Sales Rules Disabled in registry' => false
            ),
            array(
                'Sales Rules are blocked'          => true,
                'Apply Sales Rules in cfg'         => false,
                'Sales Rules Disabled in registry' => true
            ),
            array(
                'Sales Rules are blocked'          => true,
                'Apply Sales Rules in cfg'         => false,
                'Sales Rules Disabled in registry' => false
            )
        );
    }

    /**
     * @after
     */
    public function tearDown()
    {
        Mage::unregister('shopgate_disable_sales_rules');
    }
}
