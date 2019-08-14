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
 * Manage Connections Grid
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Block_Adminhtml_OAuth_Manage_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('shopgate_oauth_connections');
        $this->setDefaultSort('scope_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);

        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'shopgate/shopgate_connection_grid_collection';
    }

    /**
     * Prepares the collection
     *
     * @return Shopgate_Framework_Model_Resource_Shopgate_Connection_Collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Register grid columns
     *
     * @return Shopgate_Framework_Block_Adminhtml_OAuth_Manage
     */
    protected function _prepareColumns()
    {
        // 		$this->addColumn('country', array(
        // 			'header' => Mage::helper('sales')->__('Tax default country'),
        // 			'index' => 'country',
        // 			'type' => 'country',
        // 		));

        // 		$this->addColumn('currency', array(
        // 			'header' => Mage::helper('sales')->__('Base currency'),
        // 			'index' => 'currency',
        // 			'type' => 'currency',
        // 		));

        // 		$this->addColumn('created_at', array(
        // 			'header' => Mage::helper('sales')->__('Established on'),
        // 			'index' => 'created_at',
        // 			'type' => 'datetime',
        // 			'width' => '100px',
        // 		));

        $this->addColumn('default_store_view', array(
            'header' => Mage::helper('shopgate')->__('Default Store View'),
            'index'  => 'default_store_view',
            'type'   => 'store',
            'width'  => '100px',
        ));

        $this->addColumn('shop_number', array(
            'header' => Mage::helper('shopgate')->__('Shop Number #'),
            'width'  => '40px',
            'type'   => 'text',
            'index'  => 'shop_number',
        ));

        $this->addColumn('mobile_alias', array(
            'header' => Mage::helper('shopgate')->__('Mobile Url'),
            'index'  => 'mobile_alias',
            'type'   => 'text',
            'width'  => '120px',
        ));

        $this->addColumn('related_store_views', array(
            'header' => Mage::helper('shopgate')->__('Related Store Views'),
            'index'  => 'related_store_views',
            'type'   => 'store',
            'width'  => '100px',
        ));

        $this->addColumn('status', array(
            'header'  => Mage::helper('shopgate')->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'width'   => '70px',
            'options' => Mage::getSingleton('shopgate/system_config_source_enabledisable')->getOptionArray(),
        ));

        $this->addColumn('action',
                         array(
                             'header'    => Mage::helper('shopgate')->__('Action'),
                             'width'     => '50px',
                             'type'      => 'action',
                             'getter'    => 'getId',
                             'actions'   => array(
                                 array(
                                     'caption' => Mage::helper('shopgate')->__('Configure'),
                                     'url'     => array('base' => '*/*/configure'),
                                     'field'   => 'shopgate_connection_ids'
                                 ),
                                 array(
                                     'caption' => Mage::helper('shopgate')->__('Disconnect'),
                                     'url'     => array('base' => '*/*/unregister'),
                                     'field'   => 'shopgate_connection_ids'
                                 )
                             ),
                             'filter'    => false,
                             'sortable'  => false,
                             'index'     => 'stores',
                             'is_system' => true,
                         ));

        return parent::_prepareColumns();
    }

    /**
     * registers the massactions
     *
     * @return Shopgate_Framework_Block_Adminhtml_OAuth_Manage
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shopgate_connection_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('activate_connection', array(
            'label' => Mage::helper('shopgate')->__('activate'),
            'url'   => $this->getUrl('*/*/edit/action/activate'),
        ));

        $this->getMassactionBlock()->addItem('deactivate_connection', array(
            'label' => Mage::helper('shopgate')->__('deactivate'),
            'url'   => $this->getUrl('*/*/edit/action/deactivate'),
        ));

        return $this;
    }

    /**
     * Disables the row javascript link
     *
     * @param $row
     *
     * @return mixed
     */
    public function getRowUrl($row)
    {
        return Mage::helper('shopgate/config')->getConfigureUrl($row->getConfigId());
    }
}