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
 * User: Peter Liebig
 * Date: 23.01.14
 * Time: 00:01
 * E-Mail: p.liebig@me.com
 */

/**
 * entry point for api requests
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
include_once Mage::getBaseDir("lib") . '/Shopgate/shopgate.php';

class Shopgate_Framework_FrameworkController extends Mage_Core_Controller_Front_Action
{
    const RECEIVE_AUTH_ACTION = 'receive_authorization';

    /**
     * load the module and do api-request
     */
    public function preDispatch()
    {
        if (Mage::app()->getRequest()->getActionName() == self::RECEIVE_AUTH_ACTION) {
            Mage::app()->getRequest()->setParam('action', self::RECEIVE_AUTH_ACTION);
        }

        $this->_run();
    }

    /**
     * placeholder action, needs to be defined for router
     */
    public function receive_authorizationAction()
    {
        $this->_run();
    }

    /**
     * index action -> call run
     */
    public function indexAction()
    {
        $this->_run();
    }

    /**
     * run
     */
    protected function _run()
    {
        define("_SHOPGATE_API", true);
        define("_SHOPGATE_ACTION", Mage::app()->getRequest()->getParam("action"));
        define("SHOPGATE_PLUGIN_VERSION", Mage::helper("shopgate")->getModuleVersion());

        try {
            $config = Mage::helper("shopgate/config")->getConfig();
            if (!Mage::getStoreConfig(Shopgate_Framework_Model_Config::XML_PATH_SHOPGATE_ACTIVE)
                && Mage::app()->getRequest()->getParam("action") != self::RECEIVE_AUTH_ACTION
            ) {
                throw new ShopgateLibraryException(ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE, 'plugin not active', true);
            }
            Mage::app()->loadArea("adminhtml");
            Mage::app()->getTranslator()->init("adminhtml", true);
            $netCountries = $config->getNetMarketCountries();
            $country      = Mage::getStoreConfig("tax/defaults/country", $config->getStoreViewId());
            $builder      = new ShopgateBuilder($config);
            $plugin       = Mage::getModel('shopgate/shopgate_plugin', $builder);
            if (in_array($country, $netCountries)) {
                $plugin->setUseTaxClasses(true);
            }
            $plugin->handleRequest(Mage::app()->getRequest()->getParams());
        } catch (ShopgateLibraryException $e) {
            $response = new ShopgatePluginApiResponseAppJson(
                (isset($_REQUEST["trace_id"]) ? $_REQUEST["trace_id"] : ""));
            $response->markError($e->getCode(), $e->getMessage());
            $response->setData(array());
            $response->send();
        } catch (Exception $e) {
            Mage::logException($e);
            echo "ERROR";
        }

        exit;
    }
}
