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
 * Date: 20.03.14
 * Time: 11:22
 * E-Mail: p.liebig@me.com
 */

/**
 * xml export for review
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Review_Xml extends Shopgate_Model_Review
{
    /**
     * @var Mage_Review_Model_Review $item
     */
    protected $item;

    /**
     * set id
     */
    public function setUid()
    {
        parent::setUid($this->item->getId());
    }

    /**
     * set product id for the review
     */
    public function setItemUid()
    {
        parent::setItemUid($this->item->getEntityPkValue());
    }

    /**
     * set score for the review
     */
    public function setScore()
    {
        parent::setScore($this->_getScore());
    }

    /**
     * set username for the review
     */
    public function setReviewerName()
    {
        parent::setReviewerName($this->item->getNickname());
    }

    /**
     * set text for the review
     */
    public function setDate()
    {
        parent::setDate(date('Y-m-d', strtotime($this->item->getCreatedAt())));
    }

    /**
     * set title for the review
     */
    public function setTitle()
    {
        parent::setTitle($this->item->getTitle());
    }

    /**
     * set text for the review
     */
    public function setText()
    {
        parent::setText($this->item->getDetail());
    }

    /**
     * @return float|number
     */
    protected function _getScore()
    {
        $ratings = array();
        foreach ($this->item->getRatingVotes() as $vote) {
            $ratings[] = $vote->getPercent();
        }
        $sum = array_sum($ratings);
        $avg = $sum > 0 ? array_sum($ratings) / count($ratings) : $sum;
        $avg = round($avg / 10);

        return $avg;
    }
}
