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
 * Time: 15:00
 * E-Mail: p.liebig@me.com
 */

/**
 * Helper for customer related stuff
 *
 * @author      Shopgate GmbH, 35510 Butzbach, DE
 * @package     Shopgate_Framework
 */
class Shopgate_Framework_Helper_Customer extends Shopgate_Framework_Helper_Data
{
    /**
     * @param ShopgateAddress $shopgateCustomerAddress
     * @return Mage_Customer_Model_Address
     */
    public function getMagentoCustomerAddress(ShopgateAddress $shopgateCustomerAddress)
    {
        $region = $this->getMagentoRegionByShopgateAddress($shopgateCustomerAddress);

        /** @var Mage_Customer_Model_Address $magentoCustomerAddress */
        $magentoCustomerAddress = Mage::getModel("customer/address");
        $magentoCustomerAddress->setIsActive(true);
        $magentoCustomerAddress->setFirstname($shopgateCustomerAddress->getFirstName());
        $magentoCustomerAddress->setLastname($shopgateCustomerAddress->getLastName());
        $magentoCustomerAddress->setCompany($shopgateCustomerAddress->getCompany());
        $magentoCustomerAddress->setStreet($shopgateCustomerAddress->getStreet1());
        $magentoCustomerAddress->setCity($shopgateCustomerAddress->getCity());
        $magentoCustomerAddress->setPostcode($shopgateCustomerAddress->getZipcode());
        $magentoCustomerAddress->setCountryId($shopgateCustomerAddress->getCountry());
        $magentoCustomerAddress->setRegion($region->getId());
        $magentoCustomerAddress->setIsDeliveryAddress($shopgateCustomerAddress->getIsDeliveryAddress());
        $magentoCustomerAddress->setIsInvoiceAddress($shopgateCustomerAddress->getIsInvoiceAddress());

        if ($shopgateCustomerAddress->getMobile()) {
            $magentoCustomerAddress->setTelephone($shopgateCustomerAddress->getMobile());
        } else {
            if ($shopgateCustomerAddress->getPhone()) {
                $magentoCustomerAddress->setTelephone($shopgateCustomerAddress->getPhone());
            }
        }

        return $magentoCustomerAddress;
    }

    /**
     * Get Magento region model by given ShopgateAddress
     *
     * @param ShopgateAddress $address
     * @return Mage_Directory_Model_Region|NULL
     */
    public function getMagentoRegionByShopgateAddress(ShopgateAddress $address)
    {
        $map = Mage::helper('shopgate')->_getIsoToMagentoMapping();

        if (!$address->getState()) {
            return new Varien_Object();
        }

        $state  = preg_replace("/{$address->getCountry()}\-/", "", $address->getState());
        $region = Mage::getModel("directory/region")->getCollection()
                      ->addRegionCodeFilter($state)
                      ->addCountryFilter($address->getCountry())
                      ->getFirstItem();

        // If no region was found
        if (!$region->getId() && !empty($state) && isset($map[$address->getCountry()][$state])) {
            $regionCode = $map[$address->getCountry()][$state];

            /** @var Mage_Directory_Model_Resource_Region_Collection $region */
            $region = Mage::getModel("directory/region")->getCollection()
                          ->addRegionCodeFilter($regionCode)
                          ->addCountryFilter($address->getCountry())
                          ->getFirstItem();
        }

        return $region;
    }

    /**
     * get gender according to shopgate needs
     *
     * @param Mage_Customer_Model_Customer|Mage_Customer_Model_Address $data
     * @return string
     */
    public function getShopgateCustomerGender($data)
    {
        $options = Mage::getResourceModel('customer/customer')
                       ->getAttribute('gender')
                       ->getSource()
                       ->getAllOptions(false);
        $gender  = null;
        foreach ($options as $option) {
            if ($option['value'] == $data->getGender()) {
                $gender = $option['label'];
            }
        }

        switch ($gender) {
            case 'Male':
                return ShopgateCustomer::MALE;
                break;
            case 'Female':
                return ShopgateCustomer::FEMALE;
                break;
            default:
                return '';
        }
    }

    /**
     * @param $shopgateGender
     * @return string
     */
    public function getMagentoCustomerGender($shopgateGender)
    {
        $gender = Mage::getResourceModel('customer/customer')
                      ->getAttribute('gender')
                      ->getSource();

        switch ($shopgateGender) {
            case ShopgateCustomer::MALE:
                return $gender->getOptionId('Male');
                break;
            case ShopgateCustomer::FEMALE:
                return $gender->getOptionId('Female');
                break;
            default:
                return '';
        }
    }


    /**
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @return array $collection
     */
    public function getShopgateCustomerGroups($magentoCustomer)
    {
        $collection = Mage::getModel('customer/group')->getCollection();

        if (!$magentoCustomer->getId()) {
            $collection
                ->addFieldToFilter('customer_group_code', 'NOT LOGGED IN');
        } else {
            $collection
                ->addFieldToFilter('customer_group_id', $magentoCustomer->getGroupId());
        }

        $groups = array();
        foreach ($collection->getItems() as $customerGroup) {
            $group = array();

            $group['id']   = $customerGroup->getCustomerGroupId();
            $group['name'] = $customerGroup->getCustomerGroupCode();

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @param ShopgateCustomer             $shopgateCustomer
     */
    public function registerCustomer($magentoCustomer, $shopgateCustomer)
    {
	    $magentoCustomer = $this->_registerSetBasicData($magentoCustomer, $shopgateCustomer);
        $this->_registerAddCustomerAddresses($magentoCustomer, $shopgateCustomer);
    }

    /**
     * Set customers basic data like name, gender etc.
     *
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @param ShopgateCustomer             $shopgateCustomer
     *
     * @return Mage_Customer_Model_Customer $magentoCustomer
     */
    protected function _registerSetBasicData($magentoCustomer, $shopgateCustomer)
    {
        $magentoCustomer->setConfirmation(null);
        $magentoCustomer->setFirstname($shopgateCustomer->getFirstName());
        $magentoCustomer->setLastname($shopgateCustomer->getLastName());
        $magentoCustomer->setGender($this->getMagentoCustomerGender($shopgateCustomer->getGender()));
        $magentoCustomer->setDob($shopgateCustomer->getBirthday());
        $magentoCustomer->setForceConfirmed(true);
        $magentoCustomer->save();
        $magentoCustomer->sendNewAccountEmail('registered', '', $magentoCustomer->getStore()->getId());

	    return $magentoCustomer;
    }

    /**
     * add addresses to the customer
     *
     * @param Mage_Customer_Model_Customer $magentoCustomer
     * @param ShopgateCustomer             $shopgateCustomer
     */
    protected function _registerAddCustomerAddresses($magentoCustomer, $shopgateCustomer)
    {
        foreach ($shopgateCustomer->getAddresses() as $shopgateCustomerAddress) {
            $magentoCustomerAddress = $this->getMagentoCustomerAddress($shopgateCustomerAddress);
            $magentoCustomerAddress->setCustomer($magentoCustomer);
	        $magentoCustomerAddress->setCustomerId($magentoCustomer->getId());
            $magentoCustomerAddress->save();

            if ($magentoCustomerAddress->getIsInvoiceAddress() && !$magentoCustomer->getDefaultBillingAddress()) {
                $magentoCustomer->setDefaultBilling($magentoCustomerAddress->getId());
            }

            if ($magentoCustomerAddress->getIsDeliveryAddress() && !$magentoCustomer->getDefaultShippingAddress()) {
                $magentoCustomer->setDefaultShipping($magentoCustomerAddress->getId());
            }
        }
        $magentoCustomer->save();
    }

    /**
     * add customer to cart e.g to validate customer related price rules
     *
     * @param ShopgateCart $cart
     */
    public function addCustomerToCart(&$cart)
    {
        if ($cart->getMail()) {
            /** @var Mage_Customer_Model_Customer $magentoCustomer */
            $magentoCustomer = Mage::getModel("customer/customer");
            $magentoCustomer->setWebsiteId(Mage::app()->getWebsite()->getid());
            $magentoCustomer->loadByEmail($cart->getMail());
            if ($magentoCustomer->getId()) {
                $cart->setExternalCustomerId($magentoCustomer->getId());
            }
        }
    }
}
