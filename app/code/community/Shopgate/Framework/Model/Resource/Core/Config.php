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
 * Date: 16.01.14
 * Time: 09:17
 * E-Mail: p.liebig@me.com
 */

/**
 * rewrite for config class to extend functionality
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Resource_Core_Config extends Mage_Core_Model_Config
{
    /**
     * get config data by website scope
     *
     * @param        $path
     * @param array  $allowValues
     * @param string $useAsKey
     * @return array
     */
    public function getConfigDataByWebsite($path, $allowValues = array(), $useAsKey = 'id')
    {
        $storeValues = array();
        $stores      = Mage::app()->getConfig()->getNode('websites');
        foreach ($stores->children() as $code => $store) {
            switch ($useAsKey) {
                case 'id':
                    $key = (int)$store->descend('system/website/id');
                    break;

                case 'code':
                    $key = $code;
                    break;

                case 'name':
                    $key = (string)$store->descend('system/website/name');
            }
            if ($key === false) {
                continue;
            }

            $pathValue = (string)$store->descend($path);

            if (empty($allowValues)) {
                $storeValues[$key] = $pathValue;
            } else {
                if (in_array($pathValue, $allowValues)) {
                    $storeValues[$key] = $pathValue;
                }
            }
        }
        return array_filter($storeValues);
    }
}
