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
class Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Usps
    extends Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract
{
    /**
     * Serve the method name
     *
     * @param ShopgateCartBase $order
     * @return string
     */
    protected function _fetchMethod(ShopgateCartBase $order)
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        $response = $order->getShippingInfos()->getApiResponse();

        if (!isset($response['MailService'])) {
            try {
                $methodOriginCode = $response['SvcDescription'];
            } catch (Exception $e) {
                Mage::logException(new Exception('No shipping_method in response available'));
                ShopgateLogger::getInstance()->log(
                              "  There is no shipping method available in the response [Not Mailservice nor SvcDescription contains any data]",
                              ShopgateLogger::LOGTYPE_DEBUG
                );

                /* TODO not only the method should get corrected also the carrier */
                return Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract::DEFAULT_SHIPPING_METHOD;
            }
        } else {
            $methodOriginCode = $response['MailService'];
        }

        return $this->_mapShippingMethod($methodOriginCode);
    }

    /**
     * @return array $collection
     */
    public function getAvailableMethods()
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        return explode(',', Mage::getStoreConfig('carriers/usps/allowed_methods'));
    }

    /**
     * @return string $method
     */
    public function getDefaultMethod()
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        return Shopgate_Framework_Model_Shopgate_Shipping_Mapper_Method_Abstract::DEFAULT_SHIPPING_METHOD;
    }

    /**
     * Clean service name from unsupported strings and characters
     *
     * @param  string $name
     * @return string
     */
    protected function _filterServiceName($name)
    {
        ShopgateLogger::getInstance()->log("# " . __METHOD__, ShopgateLogger::LOGTYPE_DEBUG);
        $name = (string)preg_replace(
            array('~<[^/!][^>]+>.*</[^>]+>~sU', '~\<!--.*--\>~isU', '~<[^>]+>~is'),
            '',
            html_entity_decode($name)
        );
        $name = str_replace('*', '', $name);

        return $name;
    }

    /**
     * Maps incoming USPS method to internal method_id.
     *
     * @param   string $method
     * @return string
     */
    protected function _mapShippingMethod($method)
    {
        $method = $this->_filterServiceName($method);

        /** @var Mage_Usa_Model_Shipping_Carrier_Usps_Source_Method $usps */
        $usps = Mage::getModel('usa/shipping_carrier_usps_source_method');

        foreach ($usps->toOptionArray() as $methodArr) {

            if ($methodArr['label'] == $method) {
                return $methodArr['value'];
            }
        }

        return $method;
    }
}
