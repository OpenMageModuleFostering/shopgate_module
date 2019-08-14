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
 */
class Shopgate_Framework_Test_Model_Utility extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Classes short name to initialize,
     * this is the class functions you
     * will be testing by calling $this->class
     */
    const CLASS_SHORT_NAME = '';

    /**
     * Exclude group 'empty' to avoid
     * inflating the test number
     *
     * @coversNothing
     * @group empty
     */
    public function testEmpty() { }

    /**
     * Added support for PHP version 5.2
     * constant retrieval
     *
     * @param string $input
     *
     * @return mixed
     */
    protected final function getConstant($input)
    {
        $configClass = new ReflectionClass($this);

        return $configClass->getConstant($input);
    }

    /**
     * Enable module if it's installed.
     * Throw a skip if it's not.
     *
     * @param string $modulePath - magento module as defined in modules/config.xml
     */
    protected function activateModule($modulePath)
    {
        $config = $this->getModuleConfig($modulePath);

        if (!$config) {
            return;
        }

        $config->active = 'true';
    }

    /**
     * Disable module if it's installed.
     * Throw a skip if it's not.
     *
     * @param string $modulePath - magento module as defined in modules/config.xml
     */
    protected function deactivateModule($modulePath)
    {
        $config = $this->getModuleConfig($modulePath);

        if (!$config) {
            return;
        }

        $config->active = 'false';
    }

    /**
     * Handles grabbing module configuration and
     * throwing a method skip if nothing is returned
     *
     * @param $modulePath
     * @return Varien_Simplexml_Object
     */
    protected function getModuleConfig($modulePath)
    {
        $config = Mage::getConfig()->getModuleConfig($modulePath);

        if (!$config) {
            $this->markTestSkipped($modulePath . ' plugin is not installed');
        }

        return $config;
    }

    /**
     * Enables module via inline fixture
     *
     * @param $xmlPath - core_config_data path to module's activation
     */
    protected function enableModule($xmlPath)
    {
        $this->setConfig($xmlPath, 1);
    }

    /**
     * Disables module via inline fixture
     *
     * @param $xmlPath - core_config_data path to module's activation
     */
    protected function disableModule($xmlPath)
    {
        $this->setConfig($xmlPath, 0);
    }

    /**
     * Sets the default config fixture for path
     *
     * @param $xmlPath - xml path of the fixture of store 0
     * @param $value   - value to set for the fixture
     */
    private function setConfig($xmlPath, $value)
    {
        Mage::app()->getStore(0)->setConfig($xmlPath, $value);
    }
}