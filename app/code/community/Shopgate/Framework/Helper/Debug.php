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
 * User: Peter Liebig
 * Date: 06.02.14
 * Time: 13:17
 * E-Mail: p.liebig@me.com
 */

/**
 * debug helper shopgate
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Helper_Debug extends Mage_Core_Helper_Abstract
{
    /**
     * return all info data according to installed magento version
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'Magento-rewrites'  => Mage::getConfig()->getNode()->xpath('//global//rewrite'),
            'Magento-conflicts' => $this->_getRewrites(),
            'Magento-modules'   => $this->_getModules(),
        );
    }

    /**
     * Retrieve a collection of all modules registered
     *
     * @return array|mixed[]
     */
    protected function _getModules()
    {
        $modules        = Mage::getConfig()->getNode('modules')->children();
        $arrayOfModules = array();
        foreach ($modules as $modName => $module) {
            $arrayOfModules[$modName] = $module->asArray();
        }
        return $arrayOfModules;
    }

    /**
     * Retrieve a collection of all rewrites
     *
     * @return Varien_Data_Collection Collection
     */
    protected function _getRewrites()
    {
        $collection = array();
        $modules    = Mage::getConfig()->getNode('modules')->children();
        $rewrites   = array();

        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . 'config.xml';
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);

                    if ($xml instanceof SimpleXMLElement) {
                        $rewrites[$modName] = $xml->xpath('//rewrite');
                    }
                }
            }
        }

        foreach ($rewrites as $rewriteNodes) {
            foreach ($rewriteNodes as $n) {
                $nParent    = $n->xpath('..');
                $module     = (string)$nParent[0]->getName();
                $nSubParent = $nParent[0]->xpath('..');
                $component  = (string)$nSubParent[0]->getName();

                if (!in_array($component, array('blocks', 'helpers', 'models'))) {
                    continue;
                }

                $pathNodes = $n->children();
                foreach ($pathNodes as $pathNode) {
                    $path             = (string)$pathNode->getName();
                    $completePath     = $module . '/' . $path;
                    $rewriteClassName = (string)$pathNode;
                    $instance         = Mage::getConfig()->getGroupedClassName(
                                            substr($component, 0, -1),
                                            $completePath
                    );

                    if (($instance != $rewriteClassName)) {
                        array_push(
                            $collection,
                            array(
                                'type'          => $component,
                                'path'          => $completePath,
                                'rewrite_class' => $rewriteClassName,
                                'active_class'  => $instance,
                                'conflict'      => ($instance == $rewriteClassName) ? "NO" : "YES"
                            )
                        );
                    }
                }
            }
        }

        return $collection;
    }
}
