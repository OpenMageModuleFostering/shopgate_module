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
 * @coversDefaultClass Shopgate_Framework_Model_Modules_Affiliate_Router
 */
class Shopgate_Framework_Test_Model_Modules_Affiliate_RouterTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @param string $expected - expected value of the parameter
     * @param array  $params   - parameter list passed to setTrackingParameters
     *
     * @uses         ShopgateOrder::setTrackingGetParameters
     * @covers       ::getAffiliateParameter
     *
     * @dataProvider affiliateParamDataProvider
     */
    public function testGetAffiliateParameter($expected, $params)
    {
        $router    = $this->getRouter($params);
        $parameter = $router->getAffiliateParameter();
        $actual    = isset($parameter['value']) ? $parameter['value'] : '';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param string $expected - class name returned from method call result
     * @param array  $params   - parameter list passed to setTrackingParameters
     *
     * @dataProvider validatorDataProvider
     */
    public function testGetValidator($expected, $params)
    {
        //core_config_data key rewrite of a parameter that maps to Magestore
        Mage::app()->getStore(0)->setConfig('affiliateplus/refer/url_param_array', ',acc,account');
        $router    = $this->getRouter($params);
        $validator = $router->getValidator();

        $this->assertInstanceOf($expected, $validator);
    }

    /**
     * @expectedException Exception
     */
    public function testBadConstructorCall()
    {
        Mage::getModel('shopgate/modules_affiliate_router', array());
    }

    /**
     * Simple data sets
     *
     * @return array
     */
    public function affiliateParamDataProvider()
    {
        return array(
            array(
                'expected' => '12345',
                'params'   => array(
                    array('key' => 'test key', 'value' => 'test value'),
                    array('key' => 'acc', 'value' => '12345'),
                    array('key' => 'test_key2', 'value' => 'test_value2')
                )
            ),
            array(
                'expected' => 'hello',
                'params'   => array(
                    array('key' => 'acc', 'value' => 'hello'),
                    array('key' => 'test key', 'value' => 'test value'),
                    array('key' => 'test_key2', 'value' => 'test_value2')
                )
            ),
            array(
                'expected' => false,
                'params'   => array()
            ),
        );
    }

    /**
     * @return array
     */
    public function validatorDataProvider()
    {
        return array(
            array(
                'Shopgate_Framework_Model_Modules_Affiliate_Packages_Magestore_Validator',
                'testing default param mapping' => array(
                    array('key' => 'test key', 'value' => 'test value'),
                    array('key' => 'acc', 'value' => '12345'),
                    array('key' => 'test_key2', 'value' => 'test_value2')
                )
            ),
            array(
                'Shopgate_Framework_Model_Modules_Affiliate_Packages_Magestore_Validator',
                'param rewrite in db config' => array(
                    array('key' => 'test key', 'value' => 'test value'),
                    array('key' => 'account', 'value' => '12345'),
                    array('key' => 'test_key2', 'value' => 'test_value2')
                )
            ),
            array(
                'Shopgate_Framework_Model_Modules_Validator',
                'param rewrite in db config' => array(
                    array('key' => 'test key', 'value' => 'test value'),
                )
            ),

        );
    }

    /**
     * Router retriever
     *
     * @param $params - parameter list passed to setTrackingParameters
     * @return Shopgate_Framework_Model_Modules_Affiliate_Router
     */
    private function getRouter($params)
    {
        $sgOrder = new ShopgateOrder();
        $sgOrder->setTrackingGetParameters($params);

        return Mage::getModel('shopgate/modules_affiliate_router', array($sgOrder));
    }
}