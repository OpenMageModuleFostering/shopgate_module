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
 * @author Konstantin Kiritsenko <konstantin.kiritsenko@shopgate.com>
 */
class Shopgate_Framework_Test_Model_Payment_RouterAbstract extends Shopgate_Framework_Test_Model_Utility
{
    /**
     * @var Shopgate_Framework_Model_Payment_Router $router
     */
    protected $router;

    /**
     * Exclude group 'empty' to avoid
     * inflating the test number
     *
     * @coversNothing
     * @group empty
     */
    public function testEmpty() { }

    /**
     * Default setup for routers
     * Avoids throwing exception when the setup
     * is ran from this class.
     *
     * @throws Shopgate_Framework_Test_Model_Payment_Router_Exception
     */
    public function setUp()
    {
        $order     = new ShopgateOrder();
        $shortName = $this->getConstant('CLASS_SHORT_NAME');
        if (!$shortName && !$this->currentClass()) {
            throw new Shopgate_Framework_Test_Model_Payment_Router_Exception(
                "'A router's short name variable needs to be provided"
            );
        }
        $this->router = Mage::getModel($shortName, array($order));
    }

    /**
     * Checks if we are calling
     * this class directly
     *
     * @return bool
     */
    private function currentClass()
    {
        return $this instanceof self;
    }

    /**
     * Garbage collection prep
     */
    public function tearDown()
    {
        unset($this->router);
    }
}