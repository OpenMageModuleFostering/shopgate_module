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
 * Route methods to a correct Payment Class & its method
 *
 * Class Shopgate_Framework_Model_Payment_Factory
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Factory extends Shopgate_Framework_Model_Payment_Abstract
{
    /**
     * @var null | false | Shopgate_Framework_Model_Payment_Interface
     */
    protected $_payment_class = null;

    /**
     * @return bool|Shopgate_Framework_Model_Payment_Abstract
     */
    public function getPaymentClass()
    {
        if (!$this->_payment_class) {
            $this->_payment_class = $this->calculatePaymentClass();
        }

        return $this->_payment_class;
    }

    /**
     * @param Shopgate_Framework_Model_Payment_Abstract $paymentClass
     */
    public function setPaymentClass(Shopgate_Framework_Model_Payment_Abstract $paymentClass)
    {
        $this->_payment_class = $paymentClass;
    }

    /**
     * Calculates the correct payment class needed
     * Note: any class added here must inherit from Payment_Abstract
     *
     * @return bool|Shopgate_Framework_Model_Payment_Interface
     * @throws Exception
     */
    public function calculatePaymentClass()
    {
        if ($this->isSimpleClass()):
            return Mage::getModel('shopgate/payment_simple', array($this->getShopgateOrder()))->getMethodModel();
        elseif ($this->isComplexClass()):
            return Mage::getModel('shopgate/payment_router', array($this->getShopgateOrder()))->getMethodModel();
        else:
            return false;
        endif;
    }

    /**
     * Runs initial setup functions
     */
    public function setUp()
    {
        if ($this->validatePaymentClass()) {
            $this->getPaymentClass()->setUp();
        }

        parent::setUp();
    }

    /**
     * Create order router
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return Mage_Sales_Model_Order
     */
    public function createNewOrder($quote)
    {
        if ($this->validatePaymentClass()) {
            return $this->getPaymentClass()->createNewOrder($quote);
        }

        return parent::createNewOrder($quote);
    }

    /**
     * Manipulate order router
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($magentoOrder)
    {
        $magentoOrder = $this->_beforeOrderManipulate($magentoOrder);

        $magentoOrder = $this->validatePaymentClass()
            ? $this->getPaymentClass()->manipulateOrderWithPaymentData($magentoOrder)
            : parent::manipulateOrderWithPaymentData($magentoOrder);

        $magentoOrder = $this->_afterOrderManipulate($magentoOrder);

        return $magentoOrder;
    }

    /**
     * Router for quoute preparation
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $info
     *
     * @return Mage_Sales_Model_Quote
     */
    public function prepareQuote($quote, $info)
    {
        if ($this->validatePaymentClass()) {
            return $this->getPaymentClass()->prepareQuote($quote, $info);
        }

        return parent::prepareQuote($quote, $info);
    }

    /**
     * Router for order status setting
     *
     * @param Mage_Sales_Model_Order $magentoOrder
     *
     * @return mixed
     */
    public function setOrderStatus($magentoOrder)
    {
        if ($this->validatePaymentClass()) {
            return $this->getPaymentClass()->setOrderStatus($magentoOrder);
        }

        return parent::setOrderStatus($magentoOrder);
    }

    /**
     * Router for grabbing the correct payment model
     *
     * @return bool | mixed
     */
    public function getPaymentModel()
    {
        if ($this->validatePaymentClass()) {
            return $this->getPaymentClass()->getPaymentModel();
        }

        return parent::getPaymentModel();
    }

    /**
     * Checks if payment class exists, it is valid & has method
     *
     * @return bool
     */
    public function validatePaymentClass()
    {
        /** @var Shopgate_Framework_Model_Payment_Abstract $paymentClass */
        $paymentClass = $this->getPaymentClass();

        if ($paymentClass instanceof Shopgate_Framework_Model_Payment_Interface) {
            if ($paymentClass && $paymentClass->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * A simple class will contain only one word
     * inside payment_method, e.g. PAYPAL
     *
     * @return bool
     */
    protected function isSimpleClass()
    {
        if ($this->getPaymentMethod()) {
            $parts = explode('_', $this->getPaymentMethod());

            return count($parts) === 1;
        }

        return false;
    }

    /**
     * A complex class will contain more than one word
     * inside a payment_method, e.g. AUTHN_CC
     *
     * @return bool
     */
    protected function isComplexClass()
    {
        if ($this->getPaymentMethod()) {
            $parts = explode('_', $this->getPaymentMethod());

            return count($parts) > 1;
        }

        return false;
    }
}