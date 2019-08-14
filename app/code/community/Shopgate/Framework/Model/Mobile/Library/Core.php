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
class Shopgate_Framework_Model_Mobile_Library_Core extends ShopgateBuilder
{
    /**
     * Copied over for class rewrite purposes
     *
     * @param string $userAgent - user agent
     * @param array  $get       - GET params
     * @param array  $cookie    - COOKIE params
     *
     * @return Shopgate_Framework_Model_Mobile_Library_MobileRedirect
     */
    public function buildMobileRedirect($userAgent, array $get, array $cookie)
    {
        $settingsManager = new Shopgate_Helper_Redirect_SettingsManager($this->config, $get, $cookie);
        $templateParser  = new Shopgate_Helper_Redirect_TemplateParser();
        $linkBuilder     = new Shopgate_Helper_Redirect_LinkBuilder($settingsManager, $templateParser);
        $tagsGenerator   = new Shopgate_Helper_Redirect_TagsGenerator($linkBuilder, $templateParser);
        $redirector      = new Shopgate_Helper_Redirect_Redirector(
            $settingsManager,
            new Shopgate_Helper_Redirect_KeywordsManager(
                $this->buildMerchantApi(),
                $this->config->getRedirectKeywordCachePath(),
                $this->config->getRedirectSkipKeywordCachePath()
            ),
            $linkBuilder,
            $userAgent
        );


        return new Shopgate_Framework_Model_Mobile_Library_MobileRedirect(
            $redirector,
            $tagsGenerator,
            $settingsManager,
            $templateParser,
            Mage::getBaseDir('lib') . '/Shopgate/assets/js_header.html',
            $this->config->getShopNumber()
        );
    }


}