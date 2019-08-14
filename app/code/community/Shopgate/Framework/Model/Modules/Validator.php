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
class Shopgate_Framework_Model_Modules_Validator implements Shopgate_Framework_Model_Interfaces_Modules_Validator
{
    const XML_CONFIG_ENABLED = '';
    const MODULE_CONFIG      = '';

    /**
     * All around check for whether module is the one to use
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->isEnabled() && $this->isModuleActive() && $this->checkGenericValid();
    }

    /**
     * Checks store config to be active
     *
     * @return bool
     */
    public function isEnabled()
    {
        $config  = $this->getConstant('XML_CONFIG_ENABLED');
        $val     = Mage::getStoreConfig($config);
        $enabled = !empty($val);
        if (!$enabled) {
            $debug = Mage::helper('shopgate')->__(
                'Enabled check by path "%s" was evaluated as empty: "%s" in class "%s"',
                $config,
                $val,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $enabled;
    }

    /**
     * Checks module node to be active
     *
     * @return mixed
     */
    public function isModuleActive()
    {
        $config = $this->getConstant('MODULE_CONFIG');
        $active = Mage::getConfig()->getModuleConfig($config)->is('active', 'true');

        if (!$active) {
            $debug = Mage::helper('shopgate')->__(
                'Module by config "%s" was not active in class "%s"',
                $config,
                get_class($this)
            );
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $active;
    }

    /**
     * Implement any custom validation
     *
     * @return bool
     */
    public function checkGenericValid()
    {
        return true;
    }

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
}