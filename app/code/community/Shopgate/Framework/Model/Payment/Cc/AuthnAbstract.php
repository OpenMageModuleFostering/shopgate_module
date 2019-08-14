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
 * Class Shopgate_Framework_Model_Payment_Cc_AuthnAbstract
 *
 * @author Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Cc_AuthnAbstract extends Shopgate_Framework_Model_Payment_Cc_Abstract
{
    const PAYMENT_IDENTIFIER = ShopgateOrder::AUTHN_CC;

    /**
     * const for transaction types of shopgate
     */
    const SHOPGATE_PAYMENT_STATUS_AUTH_ONLY    = 'auth_only';
    const SHOPGATE_PAYMENT_STATUS_AUTH_CAPTURE = 'auth_capture';

    /**
     * const for response codes
     */
    const RESPONSE_CODE_APPROVED = 1;
    const RESPONSE_CODE_DECLINED = 2;
    const RESPONSE_CODE_ERROR    = 3;
    const RESPONSE_CODE_HELD     = 4;

    const RESPONSE_REASON_CODE_APPROVED                  = 1;
    const RESPONSE_REASON_CODE_NOT_FOUND                 = 16;
    const RESPONSE_REASON_CODE_PARTIAL_APPROVE           = 295;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED = 252;
    const RESPONSE_REASON_CODE_PENDING_REVIEW            = 253;
    const RESPONSE_REASON_CODE_PENDING_REVIEW_DECLINED   = 254;

}