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
 * User: Konstantin Kiritsenko
 * Date: 6/2/15
 * Time: 11:10
 * Email: konstantin@kiritsenko.com
 */ 
class Shopgate_Framework_Model_Compiler_Process extends Mage_Compiler_Model_Process {
    
    /**
     * Compile classes code to files
     *
     * @return Mage_Compiler_Model_Process
     */
    protected function _compileFiles()
    {
        $classesInfo = $this->getCompileClassList();
        
        foreach ($classesInfo as $code => $classes) {
            //Hotfix for double declaration of Currency class on checkout
            if (Mage::helper("shopgate/config")->getIsMagentoVersionLower('1.7.0.2') &&
                $code === 'checkout') {
                if (($key = array_search('Mage_Directory_Model_Currency', $classes)) !== false) {
                    unset($classes[$key]);
                }
            }
            $classesSorce = $this->_getClassesSourceCode($classes, $code);
            file_put_contents(
                $this->_includeDir . DS . Varien_Autoload::SCOPE_FILE_PREFIX . $code . '.php',
                $classesSorce
            );
        }
        return $this;
    }
    
}