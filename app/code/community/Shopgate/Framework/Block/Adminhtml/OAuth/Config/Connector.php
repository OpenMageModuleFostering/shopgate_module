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
 * Time: 10:51
 * Email: konstantin@kiritsenko.com
 */
class Shopgate_Framework_Block_Adminhtml_OAuth_Config_Connector extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopgate/oauth/config/connector.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return url for Shopgate connect button
     *
     * @return string
     */
    public function getConnectUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/shopgate/connect');
    }

    /**
     * Generate Shopgate connect button html
     *
     * @return string
     */
    public function getElementHtml()
    {
        $storeId = $this->_getCurrentStoreId();
        $oauth   = $this->_getHelper()->getOauthToken($storeId);

        if (empty($oauth)) {
            $block = $this->getLayout()->createBlock('adminhtml/widget_button')
                          ->setData(
                              array(
                                  'id'      => 'shopgate_connector_button',
                                  'label'   => $this->helper('shopgate')->__('Establish connection'),
                                  'onclick' => 'javascript:window.location = \'' . $this->getConnectUrl(
                                      ) . '\'; return false;'
                              )
                          );
        } else {
            $notice = $this->helper('shopgate')->__('Connection successful');
            $block  = $this->getLayout()
                           ->createBlock('core/text', 'oauth-already-set')
                           ->setText("<p style='color: #2075C8;'>{$notice}</p>");
        }

        return $block->toHtml();
    }

    /**
     * Gets store ID of current config scope
     *
     * @return int
     * @throws Mage_Core_Exception
     */
    private function _getCurrentStoreId()
    {
        $storeName = Mage::app()->getRequest()->getParam('store');

        if (!$storeName) { //on a website config page
            $website = Mage::app()->getRequest()->getParam('website');
            return $this->_getHelper()->getStoreIdByWebsite($website);
        }
        return $this->_getHelper()->getStoreIdByStoreCode($storeName);
    }

    /**
     * @return Shopgate_Framework_Helper_Config
     */
    protected function _getHelper()
    {
        return $this->helper('shopgate/config');
    }

}