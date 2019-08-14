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
 * Date: 30.05.2014
 * Time: 08:13
 * E-Mail: steffen.meuser@shopgate.com
 */

/**
 * @package      Shopgate_Framework
 * @author       Shopgate GmbH Butzbach
 */
class Shopgate_Framework_Model_System_Config_Source_Enabledisable extends Mage_Core_Model_Abstract
{
	const STATUS_ENABLED		= 1;
	const STATUS_DISABLED		= 0;
	
	/**
	 * Retrieve option array
	 *
	 * @return array
	 */
	static public function getOptionArray()
	{
		return array(
				self::STATUS_ENABLED    => Mage::helper('shopgate')->__('Enabled'),
				self::STATUS_DISABLED   => Mage::helper('shopgate')->__('Disabled')
		);
	}
}