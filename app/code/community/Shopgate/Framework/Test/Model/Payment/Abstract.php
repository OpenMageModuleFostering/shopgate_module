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
 * @author Konstantin Kiritsenko <konstantin.kiritsenko@shopgate.com>
 *
 * @group  Shopgate_Payment
 */
class Shopgate_Framework_Test_Model_Payment_Abstract extends Shopgate_Framework_Test_Model_Utility
{
    /**
     * Object of initialized CLASS_SHORT_NAME
     *
     * @var Shopgate_Framework_Model_Payment_Abstract $class
     */
    protected $class;

    /**
     * Module config value as defined
     * in the etc/modules/Module.xml
     * e.g. Mage_Paypal
     */
    const MODULE_CONFIG = '';

    /**
     * Config_data module enabled path
     */
    const XML_CONFIG_ENABLED = '';

    /**
     * Sets up a class on unit load
     */
    public function setUp()
    {
        $classShortName = $this->getConstant('CLASS_SHORT_NAME');

        if (null === $this->class && $classShortName) {
            $this->class = Mage::getModel($classShortName, array(new ShopgateOrder()));
        }
    }

    /**
     * Test moduleName <active> node check, e.g. <Mage_Paypal><active>true</..>
     * NOTE: the tests won't run if the module is not installed
     *
     * Had issues setting up a data provider for this one,
     * config refused to be re-written on the second iteration.
     *
     * @uses    Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     * @uses    Shopgate_Framework_Test_Model_Utility::getConstant
     * @uses    Shopgate_Framework_Test_Model_Utility::activateModule
     * @uses    Shopgate_Framework_Test_Model_Utility::deactivateModule
     */
    public function testIsActive()
    {
        if ($this->isAbstractTest()) {
            return;
        }

        $moduleName = $this->getConstant('MODULE_CONFIG');
        $this->activateModule($moduleName);
        $this->assertTrue($this->class->isModuleActive());

        $this->deactivateModule($moduleName);
        $this->assertFalse($this->class->isModuleActive());
    }

    /**
     * Checks if module is enabled in config_data.
     * Not using fixtures as we will have to create
     * two files for every payment class
     *
     * @uses    Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     * @uses    Shopgate_Framework_Test_Model_Utility::getConstant
     */
    public function testIsEnabled()
    {
        if ($this->isAbstractTest()) {
            return;
        }

        $xmlPath = $this->getConstant('XML_CONFIG_ENABLED');
        $this->enableModule($xmlPath);

        $this->assertTrue($this->class->isEnabled());
    }

    /**
     * Checks if module is disabled in config_data
     *
     * @uses    Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     * @uses    Shopgate_Framework_Test_Model_Utility::getConstant
     */
    public function testIsDisabled()
    {
        if ($this->isAbstractTest()) {
            return;
        }

        $xmlPath = $this->getConstant('XML_CONFIG_ENABLED');
        $this->disableModule($xmlPath);

        $this->assertFalse($this->class->isEnabled());
    }

    /**
     * Checks the generic set function in case it was overwritten
     *
     * @uses Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     */
    public function testCheckGeneric()
    {
        if ($this->isAbstractTest()) {
            return;
        }

        $this->assertTrue($this->class->checkGenericValid());
    }

    /**
     * Tests that payment methods are using new status
     * setting functionality
     *
     * @uses    Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     */
    public function testShopgateStatusSet()
    {
        if ($this->isAbstractTest()) {
            return;
        }

        $this->setPaidStatusFixture('processing');
        $order = Mage::getModel('sales/order');
        $this->class->setOrderStatus($order);

        $this->assertTrue($order->getShopgateStatusSet());
    }

    /**
     * Tests that payment methods are using new status
     * setting functionality.
     * Ignored payment method if the paid status
     * constant is empty, those classes just do not have
     * it implemented.
     *
     * @param string $state - state from data provider
     *
     * @uses         Shopgate_Framework_Test_Model_Payment_Abstract::isAbstractTest
     * @uses         Shopgate_Framework_Test_Model_Payment_Abstract::setPaidStatusFixture
     * @uses         Shopgate_Framework_Test_Model_Utility::activateModule
     *
     * @dataProvider allStateProvider
     */
    public function testSetOrderStatus($state)
    {
        if ($this->isAbstractTest() || !$this->setPaidStatusFixture($state)) {
            return;
        }

        $this->activateModule(self::MODULE_CONFIG);

        $order = Mage::getModel('sales/order');
        $this->class->setOrderStatus($order);

        $this->assertEquals($state, $order->getState());
    }

    /**
     * Sets up config status paid fixture
     *
     * @param $state - magento order state
     * @return bool
     */
    protected function setPaidStatusFixture($state)
    {
        $reflection = new ReflectionClass($this->class);
        $constant   = $reflection->getConstant('XML_CONFIG_STATUS_PAID');
        Mage::app()->getStore(0)->setConfig($constant, $state);

        return !empty($constant);
    }

    /**
     * Sets up config status not paid fixture
     *
     * @param $state - magento order state
     * @return bool
     */
    protected function setNotPaidStatusFixture($state)
    {
        $reflection = new ReflectionClass($this->class);
        $constant   = $reflection->getConstant('XML_CONFIG_STATUS_NOT_PAID');
        Mage::app()->getStore(0)->setConfig($constant, $state);

        return !empty($constant);
    }

    /**
     * In case Abstract class is called by itself
     * we want to skip running the test as it
     * will fail
     *
     * @return bool
     */
    private function isAbstractTest()
    {
        $moduleName = $this->getConstant('MODULE_CONFIG');
        return empty($this->class) || empty($moduleName);
    }

    /**
     * Excluded states complete and closed as
     * they cannot be overwritten
     *
     * @return array
     */
    public function allStateProvider()
    {
        return array(
            'Processing'      => array(Mage_Sales_Model_Order::STATE_PROCESSING),
            'Canceled'        => array(Mage_Sales_Model_Order::STATE_CANCELED),
            'On Hold'         => array(Mage_Sales_Model_Order::STATE_HOLDED),
            'Payment Review'  => array(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW),
            'Pending Payment' => array(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT),
        );
    }

    /**
     * Garbage collection prep
     */
    public function tearDown()
    {
        unset($this->class);
    }
}