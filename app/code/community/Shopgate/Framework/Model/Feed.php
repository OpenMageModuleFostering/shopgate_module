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
 * Date: 03.02.15
 * Time: 12:32
 * E-Mail: p.liebig@me.com, peter.liebig@magcorp.de
 */

/**
 * Shopgate_Framework_Model_Feed to inform merchants about updates
 *
 * @package     Shopgate
 * @author      Peter Liebig <p.liebig@me.com, peter.liebig@magcorp.de>
 */
class Shopgate_Framework_Model_Feed
{
    const XML_PATH_SHOPGATE_RSS_URL = 'shopgate/rss/url';
    protected $_feedUrl;

    /**
     * (non-PHPdoc)
     *
     * @see Mage_AdminNotification_Model_Feed::getFeedUrl()
     */
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = Mage::getStoreConfigFlag(
                                  Mage_AdminNotification_Model_Feed::XML_USE_HTTPS_PATH
            ) ? 'https://' : 'http://';
            $this->_feedUrl .= Mage::getStoreConfig(self::XML_PATH_SHOPGATE_RSS_URL);
        }

        return $this->_feedUrl;
    }

    /**
     * check for new updates
     *
     * @return $this
     */
    public function checkUpdate()
    {
        $notificationModel = Mage::getModel('adminnotification/feed');
        if (($notificationModel->getFrequency() + $notificationModel->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $feedData[] = array(
                    'severity'    => (int)$item->severity,
                    'date_added'  => $this->getDate((string)$item->pubDate),
                    'title'       => 'Shopgate - ' . ((string)$item->title),
                    'description' => (string)$item->description,
                    'url'         => (string)$item->link,
                );
            }

            if ($feedData) {
                $notificationModel = Mage::getModel('adminnotification/inbox');
                if (!is_object($notificationModel)) {
                   return $this;
                }
                $notificationModel->parse(array_reverse($feedData));
            }

        }
        $notificationModel->setLastUpdate();

        return $this;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedData()
    {
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(
             array(
                 'timeout' => 10
             )
        );
        $curl->write(Zend_Http_Client::GET, $this->getFeedUrl(), '1.0');
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml = new SimpleXMLElement($data);
        } catch (Exception $e) {
            return false;
        }

        return $xml;
    }

    /**
     *
     * @return SimpleXMLElement
     */
    public function getFeedXml()
    {
        try {
            $data = $this->getFeedData();
            $xml  = new SimpleXMLElement($data);
        } catch (Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>');
        }

        return $xml;
    }

    /**
     * Retrieve DB date from RSS date
     *
     * @param string $rssDate
     * @return string YYYY-MM-DD YY:HH:SS
     */
    public function getDate($rssDate)
    {
        return gmdate('Y-m-d H:i:s', strtotime($rssDate));
    }
}