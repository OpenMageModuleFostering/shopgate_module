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
 * User: Konstantin Kiritsenko
 * Date: 6/4/15
 * Time: 14:43
 * Email: konstantin@kiritsenko.com
 */
class Shopgate_Framework_Model_System_Config_Source_Validation_Config extends Mage_Core_Model_Config_Data
{

    /**
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getHelper()
    {
        return Mage::helper('shopgate/config');
    }

    /**
     * oAuth config validation
     *
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function save()
    {
        $storeName = Mage::app()->getRequest()->getParam('store');
        $storeId   = $this->_getStoreIdByName($storeName);
        $oauth     = $this->_getHelper()->getOauthToken($storeId);

        //when saving config on website level
        if (!$storeName && !$oauth) {
            $this->setValue(0);
            Mage::getSingleton('core/session')->addWarning(
                Mage::helper('shopgate')->__('Store disabled. Please connect to Shopgate first.')
            );
        } elseif (!$oauth) {
            Mage::throwException(
                Mage::helper('shopgate')->__('You need to connect to Shopgate before saving the configuration')
            );
        }

        return parent::save();
    }

    /**
     * @param null|string $storeCode
     * @return int
     */
    private function _getStoreIdByName($storeCode = null)
    {
        if (!$storeCode) {
            $storeId = $this->getFieldsetDataValue('default_store');
            if (!$storeId) {
                $website = Mage::app()->getRequest()->getParam('website');
                $storeId = $this->_getHelper()->getStoreIdByWebsite($website);
            }
        } else {
            $storeId = $this->_getHelper()->getStoreIdByStoreCode($storeCode);
        }
        return (int)$storeId;
    }

}