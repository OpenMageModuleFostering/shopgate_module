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
 * User: Peter Liebig
 * Date: 27.01.14
 * Time: 15:55
 * E-Mail: p.liebig@me.com
 */

/**
 * custom config field info block for system config
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Block_Info extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $content = '
        <div class="shopgate">
            <div class="info">
                <a href="http://www.shopgate.com/" target="_blank"><img src="' .
                   $this->getSkinUrl('shopgate/logo_big.png') . '" alt="www.shopgate.com"/></a><br /><p>';
        $content .= $this->__(
                         'The settings for Shopgate can be found under the Store Settings.<br /><br />Please change the view to a single Store View.'
        );
        $content .= '</p><p><ul class="nested-content">';

        foreach (Mage::app()->getWebsites() as $website) {
            /* @var $website Mage_Core_Model_Website */
            $urlKey = '/system_config/edit/section/shopgate/website/' . $website->getCode();
            $content .= "<li><li><a href='" . Mage::helper("adminhtml")
                                                  ->getUrl($urlKey) . "'>{$website->getName()}</a>";
            $content .= "<ul>";

            foreach ($website->getGroups() as $group) {
                /** @var Mage_Core_Model_Store_Group $group */
                $content .= '<li><strong>' . $group->getName() . '</strong>';
                $content .= '<ul>';

                foreach ($group->getStores() as $store) {
                    /** @var Mage_Core_Model_Store $store */
                    $urlKey = '/system_config/edit/section/shopgate/website/' . $website->getCode() . '/store/' . $store->getCode();
                    $content .= '<li class="entry-edit"><a href="' . Mage::helper("adminhtml")->getUrl($urlKey) . '">';
                    $content .= $store->getName() . '</a></li>';
                }
                $content .= "</ul></li>";
            }
            $content .= "</ul></li>";
        }

        $content .= '
            </ul></p>
            <h3>Shopgate GmbH</h3>
            <span class="contact-type">Mail</span> <a href="mailto:technik@shopgate.com">support@shopgate.com</a><br />
            </div>
            </div>';

        return $content;
    }
}
