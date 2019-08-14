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
 * Adminhtml condition model for the condition block relation
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
abstract class Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract extends Mage_Core_Model_Abstract
    implements Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Interface
{
    /**
     * xml path to store config value for shipping api
     */
    const XML_PATH_USPS_METHODS = 'shopgate/shipping/mapping_api_mage';

    /**
     * const for default shipping method
     */
    const DEFAULT_SHIPPING_METHOD = 'Default';

    /**
     * @var
     */
    protected $_order;

    /**
     * @var
     */
    protected $_method;

    /**
     * Mapper initialization
     *
     * @param ShopgateCartBase $order
     * @return $this
     */
    public function init(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        $this->_order  = $order;
        $this->_method = $this->_fetchMethod($order);

        return $this;
    }

    /**
     * Predefined Getter for method
     * ready to be rewritten in subclasses
     */
    public function getMethod()
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        if (is_null($this->_method) && is_object($this->_order)) {
            $this->_method = $this->_fetchMethod($this->_order);
        } else {
            if (is_null($this->_method) && is_null($this->_order)) {
                Mage::logException(new Exception('Error: model not initialized properly'));
            }
        }

        ShopgateLogger::getInstance()->log(
                      "  Mapped shipping method is: '" . $this->_method . "'",
                      ShopgateLogger::LOGTYPE_DEBUG
        );

        return $this->_method;
    }

    abstract protected function _fetchMethod(ShopgateCartBase $order);
}
