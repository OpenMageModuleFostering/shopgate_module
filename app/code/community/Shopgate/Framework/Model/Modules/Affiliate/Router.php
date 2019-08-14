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
class Shopgate_Framework_Model_Modules_Affiliate_Router
    implements Shopgate_Framework_Model_Interfaces_Modules_Router
{
    const VALIDATOR        = 'validator';
    const REDEEM           = 'redeem';
    const UTILITY          = 'utility';
    const CLASS_SHORT_PATH = 'shopgate/modules_affiliate_packages';

    /**
     * @var array
     */
    private $affiliateParameters;

    /**
     * @var ShopgateOrder | ShopgateCart
     */
    private $sgOrder;

    /**
     * A map of param to module
     *
     * @var array
     */
    protected $paramModuleMap = array(
        'acc' => 'Magestore'
    );

    /**
     * @param array $data - should contain an array of affiliate params in first element
     * @throws Exception
     */
    public function __construct(array $data)
    {
        $sgOrder = current($data);
        if (!$sgOrder instanceof ShopgateCartBase) {
            $error = Mage::helper('shopgate')->__('Incorrect class provided to: %s::_constructor()', get_class($this));
            ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
            throw new Exception($error);
        }
        $this->sgOrder             = $sgOrder;
        $this->affiliateParameters = $sgOrder->getTrackingGetParameters();
    }

    /**
     * @inheritdoc
     */
    public function getValidator()
    {
        $validator = $this->getPluginModel(self::VALIDATOR);

        return $validator ? $validator : Mage::getModel('shopgate/modules_validator');
    }

    /**
     * Retrieves the utility class of affiliate package
     *
     * @param null | string $moduleName - package folder name
     *
     * @return false | Shopgate_Framework_Model_Modules_Affiliate_Utility
     */
    public function getUtility($moduleName = null)
    {
        if (!$moduleName) {
            $moduleName = $this->getModuleName();
        }
        $baseClassName = strtolower($moduleName);
        $validator     = $this->initAffiliateClass($baseClassName, self::VALIDATOR);
        $utility       = $this->initAffiliateClass($baseClassName, self::UTILITY, array($validator));

        if ($utility instanceof Shopgate_Framework_Model_Modules_Affiliate_Utility) {
            return $utility;
        }

        return false;
    }

    /**
     * Retrieves the redeemer class
     *
     * @return false | Shopgate_Framework_Model_Modules_Affiliate_Packages_Magestore_Redeem
     */
    public function getRedeemer()
    {
        return $this->getPluginModel(self::REDEEM);
    }

    /**
     * Traverses the list of affiliate parameters
     * and attempts to match it to a the module map.
     * Once the correct match is found, it returns the
     * affiliate parameter.
     *
     * @return false | array - array('key' => 'GET_KEY', 'value' => 'GET_VALUE')
     */
    public function getAffiliateParameter()
    {
        foreach ($this->affiliateParameters as $param) {
            if (isset($param['key']) && isset($this->paramModuleMap[$param['key']])) {
                return $param;
            }
        }

        return false;
    }

    /**
     * ======================================
     * ========= Helper Functions ===========
     * ======================================
     */

    /**
     * Retrieves the correct module name
     * mapping based on the affiliate parameter
     * passed
     *
     * @return bool | string
     */
    private function getModuleName()
    {
        $modulePath = $this->getParameterMapping();

        if (!$modulePath && !empty($this->affiliateParameters)) {
            $this->checkForCustomParameterKeyMapping();
            $modulePath = $this->getParameterMapping();
        }

        return $modulePath;

    }

    /**
     * Retrieves the class name of the main package on success
     *
     * @return string
     */
    private function getParameterMapping()
    {
        $parameter = $this->getAffiliateParameter();

        return !empty($parameter['key']) ? $this->paramModuleMap[$parameter['key']] : '';
    }

    /**
     * In case the module's key parameter can be customized,
     * we will need to make a call to add the custom key to
     * the module mapping array.
     *
     * @return bool
     */
    private function checkForCustomParameterKeyMapping()
    {
        foreach ($this->paramModuleMap as $param => $packageName) {
            $utility = $this->getUtility($packageName);
            if ($utility) {
                $this->paramModuleMap[$utility->getTrackingCodeKey()] = $packageName;
                unset($this->paramModuleMap[$param]);

                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves a model to access
     *
     * @param string $path
     * @return mixed
     */
    private function getPluginModel($path)
    {
        return $this->initAffiliateClass($this->getModuleName(), $path);
    }

    /**
     * Small helper that concatenate the first two params given
     * with an underscore & loads the model
     *
     * @param string $partOne - first part of class name
     * @param string $partTwo - second part of class name
     * @param array  $data    - constructor params
     *
     * @return mixed
     */
    private function initAffiliateClass($partOne, $partTwo, $data = array())
    {
        $partOne = strtolower(self::CLASS_SHORT_PATH . '_' . $partOne);
        return @ Mage::getModel($partOne . '_' . strtolower($partTwo), $data);
    }
}