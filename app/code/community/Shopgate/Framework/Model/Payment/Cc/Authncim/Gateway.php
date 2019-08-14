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
 * Added an additional call for AuthorizeCim gateway API
 *
 * Class Shopgate_Framework_Model_Payment_Cc_Authncim_Gateway
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_Authncim_Gateway extends ParadoxLabs_AuthorizeNetCim_Model_Gateway
{
    protected $recursion_counter = 0;

    /**
     * AuthnCIM extension library did not allow us to make this
     * request at the time. Had to inherit from gateway to use
     * protected _runTransaction() method. We are creating a
     * profileId and profilePaymentId base off of transactionId.
     *
     * @return array
     */
    public function createCustomerProfileFromTransactionRequest()
    {
        $result    = array();
        $params    = array(
            'transId' => $this->getParameter('transId'),
        );
        $response  = $this->_runTransaction('createCustomerProfileFromTransactionRequest', $params);
        $errorCode = $response['messages']['message']['code'];
        $errorText = $response['messages']['message']['text'];

        if (isset($response['customerProfileId'], $response['customerPaymentProfileIdList']['numericString'])) {
            $result['customerProfileId']        = $response['customerProfileId'];
            $result['customerPaymentProfileId'] = $response['customerPaymentProfileIdList']['numericString'];
        } elseif (isset($errorText)
                  && strpos($errorText, 'duplicate') !== false
        ) {
            $profileId = preg_replace('/[^0-9]/', '', $errorText);
            /**
             * If we have profileID from error, try to get paymentID based on card's last 4 digits
             */
            if (!empty($profileId)) {
                $this->setParameter('customerProfileId', $profileId);
                $profile                     = $this->getCustomerProfile();
                $result['customerProfileId'] = $profileId;

                if (isset($profile['profile']['paymentProfiles'])
                    && count($profile['profile']['paymentProfiles']) > 0
                ) {
                    $lastFour = $this->getParameter('cardNumber');
                    //match profile that has the same last 4 card digits
                    foreach ($profile['profile']['paymentProfiles'] as $card) {
                        if (isset($card['payment']['creditCard'])
                            && $lastFour == substr($card['payment']['creditCard']['cardNumber'], -4)
                        ) {
                            $result['customerPaymentProfileId'] = $card['customerPaymentProfileId'];
                            break;
                        }
                    }
                } else {
                    /**
                     * They don't have any cards in profile! Remove CIM profile & recurse.
                     * This can fail on refunding if original payment card bound to transaction
                     * does not match the imported card via Shopgate.
                     */
                    $this->deleteCustomerProfile();
                    if ($this->recursion_counter < 2) {
                        $result = $this->createCustomerProfileFromTransactionRequest();
                        $this->recursion_counter++; //not necessary, but protects from recursion leaks
                    }
                }
            } else {
                /**
                 * weird gateway error that passed _runTransaction() error throw
                 */
                $error = mage::helper('shopgate')->__(
                    'Unknown error passed through _runTransaction validation. Code "%s" Message: "%s"',
                    $errorCode,
                    $errorText
                );
                ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
            }
        }
        return $result;
    }
}