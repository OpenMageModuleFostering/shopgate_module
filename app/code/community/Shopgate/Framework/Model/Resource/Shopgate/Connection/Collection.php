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
 *  @author Shopgate GmbH <interfaces@shopgate.com>
 */

/**
 * User: Steffen Meuser
 * Date: 28.05.2014
 * Time: 10:20
 * E-Mail: steffen.meuser@shopgate.com
 */

/**
 * @package      Shopgate_Framework
 * @author       Shopgate GmbH Butzbach
 */
class Shopgate_Framework_Model_Resource_Shopgate_Connection_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	/**
	 * Event prefix
	 *
	 * @var string
	 */
	protected $_eventPrefix	= 'shopgate_connection_collection';
	
	/**
	 * Event object
	 *
	 * @var string
	 */
	protected $_eventObject	= 'connection_collection';
	
	/**
	 * Model initialization
	 *
	 */
	protected function _construct()
	{
		$this->_init('core/config_data');
	}
	
	/**
	 * Minimize usual count select
	 *
	 * @return Varien_Db_Select
	 */
	public function getSelectCountSql()
	{
		/* @var $countSelect Varien_Db_Select */
		$countSelect = parent::getSelectCountSql();
		$countSelect->resetJoinLeft();
		return $countSelect;
	}
	
	/**
	 * Reset left join
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return Mage_Eav_Model_Entity_Collection_Abstract
	 */
	protected function _getAllIdsSelect($limit = null, $offset = null)
	{
		$idsSelect = parent::_getAllIdsSelect($limit, $offset);
		$idsSelect->resetJoinLeft();
		return $idsSelect;
	}
	
	/**
	 * limit core/conifg_data collection to shopgate shopnumber related items
	 * 
	 * @return void
	 */
	protected function _beforeLoad()
	{
		$this->_filterShopgateShopNumbers();
		parent::_beforeLoad();
	}
	
	/**
	 * limit core/conifg_data collection to shopgate shopnumber related items
	 * 
	 * @return void
	 */
	protected function _filterShopgateShopNumbers()
	{
		$this
			->addFieldToFilter('path', array('in' => array(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_OAUTH_ACCESS_TOKEN)))
			->addFieldToFilter('value', array('nin' => array(NULL, '')));
	}
	
	/**
	 * Loads relevant data from other core/config_data entries for the grid
	 * 
	 * @return void
	 */
	protected function _afterLoad()
	{
		parent::_afterLoad();
		foreach($this->getItems() as $collectionItem) {
			$shopgateShopConnection = Mage::getModel('shopgate/shopgate_connection')
				->load($collectionItem->getId());

			$collectionItem->setStatus($shopgateShopConnection->getStatus())
				->setDefaultStoreView($shopgateShopConnection->getDefaultStoreViewId())
				->setRelatedStoreViews($shopgateShopConnection->getRelatedStoreViews())
				->setCurrency($shopgateShopConnection->getBaseCurrency())
				->setCountry($shopgateShopConnection->getTaxDefaultCountry())
				->setMobileAlias($shopgateShopConnection->getMobileAlias())
                ->setShopNumber($shopgateShopConnection->getShopNumber());
		}
	}
}