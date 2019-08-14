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
 * User: Steffen Meuser
 * Date: 26.05.14
 * Time: 12:29
 * E-Mail: steffen.meuser@shopgate.com
 */

/**
 * Disconnect All Connections Form
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Block_Adminhtml_OAuth_Disconnect extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add connect Button
     */
    public function __construct()
    {
        $this->_blockGroup = 'shopgate';
        $this->_controller = 'adminhtml_shopgate_disconnect';
        $this->_headerText = Mage::helper('shopgate')->__('Disconnect Form');

        parent::__construct();
    }

    /**
     * Preparing form
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     *
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id'     => 'oauth_disconnect_form',
                'action' => $this->getUrl('*/*/unregister/totally/true'),
                'method' => 'post'
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $helper   = Mage::helper('shopgate');
        $fieldset = $form->addFieldset(
                         'oauth_disconnect',
                         array(
                             'legend' => $helper->__('Disconnect your shop from shopgate'),
                             'class'  => 'fieldset-small'
                         )
        );

        $fieldset->addField('submit', 'submit', array(
            'label'    => Mage::helper('shopgate')->__('Disconnect from shopgate'),
            'required' => false,
            'value'    => 'Submit',
        ));

        if (Mage::registry('shopgate_oauth_disconnect')) {
            $form->setValues(Mage::registry('shopgate_oauth_disconnect')->getData());
        }

        return parent::_prepareForm();
    }
}