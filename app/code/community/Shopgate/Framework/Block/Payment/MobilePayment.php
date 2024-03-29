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

/** @noinspection PhpIncludeInspection */
include_once Mage::getBaseDir("lib") . '/Shopgate/shopgate.php';

/**
 * Handles mobile payment block printing in Mangeto Order view page
 */
class Shopgate_Framework_Block_Payment_MobilePayment extends Mage_Payment_Block_Info
{
    /**
     * Sets template directly
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopgate/payment/mobile_payment.phtml');
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getMethod()->getInfoInstance()->getOrder();
    }

    /**
     * @return Shopgate_Framework_Model_Shopgate_Order
     */
    public function getShopgateOrder()
    {
        return Mage::getModel('shopgate/shopgate_order')->load($this->getOrder()->getId(), 'order_id');
    }

    /**
     * @return string
     */
    public function getShopgateOrderNumber()
    {
        return $this->getShopgateOrder()->getShopgateOrderNumber();
    }

    /**
     * @return array
     */
    public function getPaymentInfos()
    {
        $data = array();

        if ($this->getShopgateOrder()->getReceivedData()) {
            $data = unserialize($this->getShopgateOrder()->getReceivedData());
            $data = $data->getPaymentInfos();
        }

        return $data;
    }

    /**
     * Error message wrapper
     *
     * @param $errorMessage - wraps the message with error markup
     *
     * @return string
     */
    public function printHtmlError($errorMessage)
    {
        $html = '';
        if (!$errorMessage) {
            return $html;
        }

        $html .= '<strong style="color: red; font-size: 1.2em;">';
        $html .= $this->__($errorMessage);
        $html .= '</strong><br/>';

        return $html;
    }

    /**
     * Helper function to print PaymentInfo
     * recursively
     *
     * @param $list - paymentInfo array
     * @param $html - don't pass anything, recrusive helper
     * @return string
     */
    public function printPaymentInfo($list, $html = '')
    {
        if (is_array($list)) {
            foreach ($list as $_key => $_value) {
                if (is_array($_value)) {
                    return $this->printPaymentInfo($_value, $html);
                } else {
                    $html .= '<span style="font-weight: bold">'
                             . $this->__(
                            uc_words($_key, ' ') . '</span> : '
                            . uc_words($_value, ' ') . '<br />'
                        );
                }
            }
        } else {
            $html .= $this->__($this->escapeHtml($list)) . '<br />';
        }

        return $html;
    }
}
