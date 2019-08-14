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
 * Model to extract the shipping carrier and method by an incoming ShopgateOrder
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Shopgate_Shipping_Mapper extends Mage_Core_Model_Abstract
{
    /**
     * default carrier const
     */
    const DEFAULT_CARRIER = 'shopgate';
    /**
     * @var
     */
    protected $_carrier;
    /**
     * @var
     */
    protected $_method;
    /**
     * @var
     */
    protected $_order;
    /**
     * @var
     */
    protected $_address;

    /**
     * Initialize the model
     *
     * @param ShopgateCartBase               $order
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Shopgate_Framework_Model_Shopgate_Shipping_Mapper
     */
    public function init(Mage_Sales_Model_Quote_Address $address, ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);

        $this->_order   = $order;
        $this->_address = $address;
        $this->_carrier = $this->_fetchCarrier($order);
        $this->_method  = $this->_fetchMethod($order);

        return $this;
    }

    /**
     * Internal helper to extract carrier
     *
     * @param ShopgateCartBase $order
     * @return mixed
     */
    protected function _fetchCarrier(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        $mapper = Mage::getModel('shopgate/shopgate_shipping_mapper_carrier');

        return $mapper->init($order)->getCarrier();
    }

    /**
     * Internal helper to extract method
     *
     * @param ShopgateCartBase $order
     * @return string
     */
    protected function _fetchMethod(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        /** @var Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract $mapper */
        $mapper = Mage::getModel('shopgate/shopgate_shipping_mapper_method_' . $this->getCarrier());
        if (!is_object($mapper)) {
            Mage::logException(
                new Exception('Error: no suitable Mapper Model found for carrier \'' . $this->getCarrier() . '\'')
            );
        }

        $method = $mapper->init($order)->getMethod();
        $method = $this->_validateMethod($method, $mapper);

        return $method;
    }

    /**
     * Getter for shipping carrier
     *
     * @return String
     */
    public function getCarrier()
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        if (is_null($this->_carrier) && is_object($this->_order)) {
            $this->_carrier = $this->_fetchCarrier($this->_order);
        } else {
            if (is_null($this->_carrier)) {
                Mage::throwException('Error: no carrier set');
            }
        }

        return $this->_carrier;
    }

    /**
     * Checks if the method is available
     * and returns a default method if not
     *
     * @param Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Interface $mapper
     * @param string                                                             $method
     * @return string
     */
    protected function _validateMethod($method, $mapper)
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        if (in_array($method, $mapper->getAvailableMethods())) {
            return $method;
        }
        Mage::logException(new Exception('could not match the given method \'' . $method . '\' to a available method'));
        $this->_carrier = self::DEFAULT_CARRIER;
        return Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Shopgate::DEFAULT_SHIPPING_METHOD;
    }

    /**
     * Getter for shipping method
     *
     * @return String
     */
    public function getMethod()
    {
        ShopgateLogger::getInstance()->log("# " . __FUNCTION__, ShopgateLogger::LOGTYPE_DEBUG);
        if (is_null($this->_method) && is_object($this->_order) && !is_null($this->_carrier)) {
            $this->_method = $this->_fetchMethod($this->_order);
        } else {
            if (is_null($this->_carrier)) {
                Mage::throwException('Error: no carrier set');
            }
        }

        return $this->_method;
    }
}
