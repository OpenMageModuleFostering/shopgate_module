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
 * Time: 08:51
 * E-Mail: p.liebig@me.com
 */

/**
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Review extends Shopgate_Framework_Model_Export_Abstract
{
    /**
     * @param Mage_Review_Model_Review $review
     * @return int
     */
    public function getItemNumber($review)
    {
        $product = Mage::getModel("catalog/product")->setStoreId($this->_getConfig()->getStoreViewId())->load($review->getEntityPkValue());
        return $product->getId();
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return int
     */
    public function getUpdateReviewId($review)
    {
        return $review->getId();
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return float
     */
    public function getScore($review)
    {
        $ratings = array();
        foreach ($review->getRatingVotes() as $vote) {
            $ratings[] = $vote->getPercent();
        }
        $sum = array_sum($ratings);
        $avg = $sum > 0 ? array_sum($ratings) / count($ratings) : $sum;
        $avg = round($avg / 10);

        return $avg;
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return string
     */
    public function getName($review)
    {
        return $review->getNickname();
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return string
     */
    public function getDate($review)
    {
        $date = $review->getCreatedAt();

        if (!empty($date)) {
            $date = date('c', strtotime($date));
        }

        return $date;
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return string
     */
    public function getTitle($review)
    {
        return $review->getTitle();
    }

    /**
     * @param Mage_Review_Model_Review $review
     * @return string
     */
    public function getText($review)
    {
        return $review->getDetail();
    }
}
