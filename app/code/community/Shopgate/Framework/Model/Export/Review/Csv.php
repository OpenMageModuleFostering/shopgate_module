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
 * Date: 20.03.14
 * Time: 10:00
 * E-Mail: p.liebig@me.com
 */

/**
 * csv export for review
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Review_Csv extends Shopgate_Framework_Model_Export_Review
{
    /**
     * @var null
     */
    protected $_defaultRow = null;

    /**
     * @var null
     */
    protected $_actionCache = null;

    /**
     * @param Mage_Review_Model_Review $review
     * @return array
     */
    public function generateData($review)
    {
        foreach (array_keys($this->_defaultRow) as $key) {
            $action = "_set" . uc_words($key, '', '_');
            if (empty($this->_actionCache[$action])) {
                $this->_actionCache[$action] = true;
            }
        }

        foreach (array_keys($this->_actionCache) as $_action) {
            if (method_exists($this, $_action)) {
                $this->{$_action}($review);
            }
        }

        return $this->_defaultRow;
    }

    /**
     * @param $defaultRow
     * @return Shopgate_Framework_Model_Export_Review_Csv
     */
    public function setDefaultRow($defaultRow)
    {
        $this->_defaultRow = $defaultRow;
        return $this;
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setItemNumber($review)
    {
        $this->_defaultRow['item_number'] = $this->getItemNumber($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setUpdateReviewId($review)
    {
        $this->_defaultRow['update_review_id'] = $this->getUpdateReviewId($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setScore($review)
    {
        $this->_defaultRow['score'] = $this->getScore($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setName($review)
    {
        $this->_defaultRow['name'] = $this->getName($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setDate($review)
    {
        $this->_defaultRow['date'] = $this->getDate($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setTitle($review)
    {
        $this->_defaultRow['title'] = $this->getTitle($review);
    }

    /**
     * @param Mage_Review_Model_Review $review
     */
    protected function _setText($review)
    {
        $this->_defaultRow['text'] = $this->getText($review);
    }
}
