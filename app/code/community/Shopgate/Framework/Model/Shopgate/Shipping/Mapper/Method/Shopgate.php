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
 * Extracts the Shipping method of a given Shopgate order
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Shopgate
    extends Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract
{
    /**
     * default method const
     */
    const DEFAULT_SHIPPING_METHOD = 'fix';

    /**
     * @param ShopgateCartBase $order
     * @return string
     */
    protected function _fetchMethod(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        return self::DEFAULT_SHIPPING_METHOD;
    }

    /**
     * @return array $collection
     */
    public function getAvailableMethods()
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        return array(
            self::DEFAULT_SHIPPING_METHOD,
        );
    }

    /**
     * @return string
     */
    public function getDefaultMethod()
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        return self::DEFAULT_SHIPPING_METHOD;
    }
}
