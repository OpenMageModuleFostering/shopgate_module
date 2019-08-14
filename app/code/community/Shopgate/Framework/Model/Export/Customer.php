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
 * Date: 18.03.14
 * Time: 17:57
 * E-Mail: p.liebig@me.com
 */

/**
 * export customer model to clean up plugin
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Model_Export_Customer extends Shopgate_Framework_Model_Export_Abstract
{
    /**
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @return ShopgateCustomer
     */
    public function loadGetCustomerData($magentoCustomer)
    {
        $shopgateCustomer = new ShopgateCustomer();
        $this->_getCustomerSetBaseData($shopgateCustomer, $magentoCustomer);
        $this->_getCustomerNewsletterSubscription($shopgateCustomer, $magentoCustomer);
        $this->_getCustomerSetAddresses($shopgateCustomer, $magentoCustomer);
        return $shopgateCustomer;
    }

    /**
     * @param ShopgateCustomer             $shopgateCustomer
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @return ShopgateCustomer
     */
    protected function _getCustomerSetBaseData(&$shopgateCustomer, $magentoCustomer)
    {
        $shopgateCustomer->setCustomerId($magentoCustomer->getId());
        $shopgateCustomer->setCustomerToken($this->_getCustomerToken($magentoCustomer));
        $shopgateCustomer->setFirstName($magentoCustomer->getFirstname());
        $shopgateCustomer->setLastName($magentoCustomer->getLastname());
        $shopgateCustomer->setMail($magentoCustomer->getEmail());
        $shopgateCustomer->setBirthday($magentoCustomer->getDob());
        $shopgateCustomer->setPhone($magentoCustomer->getTelephone());
        $shopgateCustomer->setGender($this->_getCustomerHelper()->getShopgateCustomerGender($magentoCustomer));

        $customerGroups = array();
        foreach ($this->_getCustomerHelper()->getShopgateCustomerGroups($magentoCustomer) as $customerGroup) {
            $customerGroups[] = new ShopgateCustomerGroup($customerGroup);
        }

        $shopgateCustomer->setCustomerGroups($customerGroups);

        return $shopgateCustomer;
    }

    /**
     * @param ShopgateCustomer             $shopgateCustomer
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @return ShopgateCustomer
     */
    protected function _getCustomerNewsletterSubscription(&$shopgateCustomer, $magentoCustomer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $newsletterSubscriber */
        $newsletterSubscriber = Mage::getModel("newsletter/subscriber");
        $newsletterSubscriber->loadByEmail($magentoCustomer->getEmail());
        $shopgateCustomer->setNewsletterSubscription($newsletterSubscriber->getSubscriberStatus() == 1);

        return $shopgateCustomer;
    }

    /**
     * @param ShopgateCustomer             $shopgateCustomer
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @return ShopgateCustomer
     */
    protected function _getCustomerSetAddresses(&$shopgateCustomer, $magentoCustomer)
    {
        $aAddresses = array();
        foreach ($magentoCustomer->getAddresses() as $magentoCustomerAddress) {
            /** @var  Mage_Customer_Model_Address $magentoCustomerAddress */
            $shopgateAddress = new ShopgateAddress();
            $shopgateAddress->setId($magentoCustomerAddress->getId());
            $shopgateAddress->setIsDeliveryAddress(1);
            $shopgateAddress->setIsInvoiceAddress(1);
            $shopgateAddress->setFirstName($magentoCustomerAddress->getFirstname());
            $shopgateAddress->setLastName($magentoCustomerAddress->getLastname());
            $shopgateAddress->setGender(
                            $this->_getCustomerHelper()->getShopgateCustomerGender($magentoCustomerAddress)
            );
            $shopgateAddress->setCompany($magentoCustomerAddress->getCompany());
            $shopgateAddress->setMail($magentoCustomerAddress->getMail());
            $shopgateAddress->setPhone($magentoCustomerAddress->getTelephone());
            $shopgateAddress->setStreet1($magentoCustomerAddress->getStreet1());
            $shopgateAddress->setStreet2($magentoCustomerAddress->getStreet2());
            $shopgateAddress->setCity($magentoCustomerAddress->getCity());
            $shopgateAddress->setZipcode($magentoCustomerAddress->getPostcode());
            $shopgateAddress->setCountry($magentoCustomerAddress->getCountry());
            $shopgateAddress->setState($this->_getHelper()->getIsoStateByMagentoRegion($magentoCustomerAddress));

            $aAddresses[] = $shopgateAddress;
        }
        $shopgateCustomer->setAddresses($aAddresses);

        return $shopgateCustomer;
    }

    /**
     * @param   Mage_Customer_Model_Customer $magentoCustomer
     * @return  string
     */
    protected function _getCustomerToken($magentoCustomer)
    {
        $relationModel = Mage::getModel('shopgate/customer')->loadByCustomerId($magentoCustomer->getId());

        if (!$relationModel->getId()) {
            $relationModel->setToken(md5($magentoCustomer->getId() . $magentoCustomer->getEmail()));
            $relationModel->setCustomerId($magentoCustomer->getId());
            $relationModel->save();
        }
        return $relationModel->getToken();
    }
}
