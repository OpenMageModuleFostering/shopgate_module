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
 * User: pliebig
 * Date: 25.03.14
 * Time: 23:09
 * E-Mail: p.liebig@me.com
 */

/**
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */

/** @var Shopgate_Framework_Model_Resource_Setup $this */
$this->startSetup();

if (!$this->getConnection()->tableColumnExists($this->getTable('shopgate_order'), 'is_test')
	&& !$this->getConnection()->tableColumnExists($this->getTable('shopgate_order'), 'is_customer_invoice_blocked')) {
	$this->run(
	    "
		ALTER TABLE `{$this->getTable('shopgate_order')}`
		ADD COLUMN `is_test` INT NOT NULL DEFAULT 0 AFTER `is_cancellation_sent_to_shopgate`,
		ADD COLUMN `is_customer_invoice_blocked` INT NOT NULL DEFAULT 0 AFTER `is_test`
		"
	);
}

$this->endSetup();
