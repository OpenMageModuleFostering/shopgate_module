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
 * Class Shopgate_Framework_Helper_Payment_Payone
 *
 * @author awesselburg <wesselburg@me.com>
 * @author Konstantin K <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Helper_Payment_Payone extends Shopgate_Framework_Helper_Payment_Abstract
{
    /**
     * @param Payone_Core_Model_Config $config
     * @param string $methodType
     *
     * @return int
     */
    public function getMethodId($config, $methodType)
    {
        if ($methodType) {
            
            $methods = $config->getPayment()->getMethodsByType($methodType);
            
            if (!empty($methods)) {
                /** @var Payone_Core_Model_Config_Payment_Method $method */
                foreach ($methods as $method) {
                    $id = $method->getScope() === 'websites'
                        ? Mage::app()->getWebsite()->getId() : Mage::app()->getStore()->getStoreId();

                    if ($method->getScopeId() === $id) {
                        return $method->getId();
                    }
                }
                $error = $this->__('PayOne: could not match config scope with any of the active methods');
            } else {
                $error = $this->__('PayOne: could not find an enabled config for mapping: %s', $methodType);
            }
        } else {
            $error = $this->__('PayOne: method type not set in the called class');
        }

        ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
        return false;
    }

}