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
 * User: pliebig
 * Date: 14.08.14
 * Time: 11:45
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * Resource model for shopgate customer relation table
 *
 * @package     Shopgate_Framework
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Resource_Customer extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize configuration data
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_init('shopgate/customer', 'id');
    }

    /**
     * Load relation by customer id
     *
     * @throws Mage_Core_Exception
     *
     * @param Shopgate_Framework_Model_Customer $customer
     * @param string                            $id
     * @return Shopgate_Framework_Model_Resource_Customer
     */
    public function loadByCustomerId(Shopgate_Framework_Model_Customer $customer, $id)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('customer_id' => $id);
        $select  = $adapter->select()
                           ->from($this->getMainTable())
                           ->where('customer_id = :customer_id');

        $customerId = $adapter->fetchOne($select, $bind);
        if ($customerId) {
            $this->load($customer, $customerId);
        } else {
            $customer->setData(array());
        }

        return $this;
    }

    /**
     * Load relation by customer $token
     *
     * @throws Mage_Core_Exception
     *
     * @param Shopgate_Framework_Model_Customer $customer
     * @param string                            $token
     * @return Shopgate_Framework_Model_Resource_Customer
     */
    public function loadByToken(Shopgate_Framework_Model_Customer $customer, $token)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('token' => $token);
        $select  = $adapter->select()
                           ->from($this->getMainTable())
                           ->where('token = :token');

        $customerId = $adapter->fetchOne($select, $bind);
        if ($customerId) {
            $this->load($customer, $customerId);
        } else {
            return false;
        }

        return $this;
    }
}