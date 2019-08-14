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
 * Handles all payment routing for Multi-Payment implementations like PayOne & PayPal.
 * It helps figure out which class model to use based on payment_method provided.
 *
 * Class Shopgate_Framework_Model_Payment_Router
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Router extends Shopgate_Framework_Model_Payment_Abstract
{
    /**
     * Default: returns 2nd part of payment_method
     * e.g. in AUTHN_CC, return CC. Simple class
     * handles the first part.
     */
    protected $_payment_method_part = 2;

    /**
     * Returns the correct Shopgate payment model
     * based on method identifier. This is recursive (sorta)!
     *
     * @return false|Shopgate_Framework_Model_Payment_Abstract
     * @throws Exception
     */
    public function getModelByPaymentMethod()
    {
        $class = $this->getClassFromMethod();
        $model = Mage::getModel($class, $this->getShopgateOrder());

        if ($model) {
            if ($model instanceof Shopgate_Framework_Model_Payment_Interface) {
                return $model;
            } elseif ($model instanceof Shopgate_Framework_Model_Payment_Router) {
                return $model->getModelByPaymentMethod();
            }
        }

        return false;
    }

    /**
     * @return bool|false|Mage_Core_Model_Abstract
     */
    public function getMethodModel()
    {
        $model = $this->getModelByPaymentMethod();

        if (!$model) {
            $model = $this->_checkAllPossibleModels();
        }

        if (!$model) {
            $debug = $this->_getHelper()->__('No class found for payment method: %s', $this->getPaymentMethod());
            ShopgateLogger::getInstance()->log($debug, ShopgateLogger::LOGTYPE_DEBUG);
        }

        return $model;
    }

    /**
     * Using payment_method as example - AUTHN_CC.
     * Adds _cc to an already resolved shopgate/payment_authn
     *
     * @return string
     */
    protected function getClassFromMethod()
    {
        $endPart = strtolower($this->_getMethodPart());
        $current = $this->getCurrentClassShortName();
        
        return $current . '_' . $endPart;
    }

    /**
     * Returns the correct part of the payment id.
     * E.g. payment_method_part = 2, will return
     * the second part of AUTHN_CC, so CC.
     *
     * @return string
     */
    protected function _getMethodPart()
    {
        //user friendly, first part (1) == 0 for array
        $index = $this->_payment_method_part;
        $parts = explode('_', $this->getPaymentMethod());
        return $parts[--$index] ? $parts[$index] : $parts[0];
    }

    /** ======= Fallback Functionality ======== */

    /**
     * Initializes models until it finds the one implementing
     * interface.
     *
     * @return bool|false|Mage_Core_Model_Abstract
     */
    protected function _checkAllPossibleModels()
    {
        $combinations = $this->_getModelCombinations();
        $class        = $this->getCurrentClassShortName();
        foreach ($combinations as $combination) {
            $className = $class . $combination;
            $model     = Mage::getModel($className, $this->getShopgateOrder());
            if ($model instanceof Shopgate_Framework_Model_Payment_Interface) {
                return $model;
            } elseif ($model instanceof Shopgate_Framework_Model_Payment_Router) {
                $model = $model->getModelByPaymentMethod();
                if ($model) {
                    return $model;
                }
            }
        }
        return false;
    }

    /**
     * Gets all possible model combinations
     * PAONE_PRP -> _Prp, _Payone, _Payone_Prp, _Prp_Payone
     *
     * @return array
     */
    protected function _getModelCombinations()
    {
        $combinations  = array();
        $paymentMethod = explode('_', strtolower($this->getPaymentMethod()));
        $this->depthPicker($paymentMethod, '', $combinations);

        return $combinations;
    }

    /**
     * Recursive array combination logic
     *
     * @param $arr
     * @param $temp_string
     * @param $collect
     */
    function depthPicker($arr, $temp_string, &$collect)
    {
        if ($temp_string != "") {
            $collect [] = $temp_string;
        }

        for ($i = 0; $i < sizeof($arr); $i++) {
            $arrcopy = $arr;
            $elem    = array_splice($arrcopy, $i, 1); // removes and returns the i'th element
            $temp    = $temp_string . "_" . $elem[0];
            if (sizeof($arrcopy) > 0) {
                $this->depthPicker($arrcopy, $temp, $collect);
            } else {
                $collect[] = $temp;
            }
        }
    }

    /**
     * Resolves current class name to magento's
     * short class. Truncates Router as well.
     * 
     * @return string
     */
    private function getCurrentClassShortName()
    {
        $class = str_replace('Shopgate_Framework_Model_', 'shopgate/', get_class($this));
        $class = preg_replace('/_Router$/', '', $class);
        return strtolower($class);
    }
}