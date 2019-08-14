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
 * Extracts the Carrier of a given ShopgateOrder
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Carrier extends Mage_Core_Model_Abstract
{
    /**
     * xml path to store view config value
     */
    const XML_PATH_CARRIER_DATA = 'shopgate/shipping/carriers';

    /**
     * @var
     */
    protected $_order;

    /**
     * @var
     */
    protected $_carrier;

    /**
     * @param ShopgateCartBase $order
     * @return $this
     */
    public function init(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        $this->_order   = $order;
        $this->_carrier = $this->_fetchCarrier($order->getShippingGroup());

        return $this;
    }

    /**
     * @param $shipping_group
     * @return mixed
     */
    protected function _fetchCarrier($shipping_group)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        $data = Mage::getStoreConfig(self::XML_PATH_CARRIER_DATA);

        if (!in_array($shipping_group, array_keys($data))) {
            ShopgateLogger::getInstance()->log(
                          "  Given shipping_group '$shipping_group' could not be mapped properly: Fallback to '" . $data['Default'] . " as carrier'",
                          ShopgateLogger::LOGTYPE_DEBUG
            );

            return $data['Default'];
        }

        return $data[$shipping_group];
    }

    /**
     * @return mixed
     */
    public function getCarrier()
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        ShopgateLogger::getInstance()->log(
                      "  Shipping carrier is mapped to: '" . $this->_carrier . "'",
                      ShopgateLogger::LOGTYPE_DEBUG
        );
        return $this->_carrier;
    }
}
