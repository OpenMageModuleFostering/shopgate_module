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
 * Main factory that gets inherited by other module factories
 */
class Shopgate_Framework_Model_Modules_Factory extends Mage_Core_Model_Abstract
{
    /** @var ShopgateOrder | ShopgateCart */
    private $sgOrder;

    /** @var Shopgate_Framework_Model_Interfaces_Modules_Router */
    private $routerModel;

    /**
     * @throws Exception
     */
    public function _construct()
    {
        $sgOrder = current($this->_data);
        $router  = next($this->_data);
        if (!$sgOrder instanceof ShopgateCartBase
            || !$router instanceof Shopgate_Framework_Model_Interfaces_Modules_Router
        ) {
            $error = Mage::helper('shopgate')->__('Incorrect class provided to: %s::_constructor()', get_class($this));
            ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
            throw new Exception($error);
        }
        $this->sgOrder     = $sgOrder;
        $this->routerModel = $router;
    }

    /** @return Shopgate_Framework_Model_Modules_Affiliate_Router */
    protected function getRouter()
    {
        return $this->routerModel;
    }

    /** @return ShopgateCart|ShopgateOrder */
    protected function getSgOrder()
    {
        return $this->sgOrder;
    }
}