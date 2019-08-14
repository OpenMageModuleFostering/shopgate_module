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
 * Class to manipulate the order payment data with BillSAFE payment data
 *
 * @author  Konstantin Kiritsenko <konstantin@kiritsenko.com>
 */
class Shopgate_Framework_Model_Payment_Simple_Billsafe
    extends Shopgate_Framework_Model_Payment_Abstract
    implements Shopgate_Framework_Model_Payment_Interface
{
    const PAYMENT_IDENTIFIER     = ShopgateOrder::BILLSAFE;
    const XML_CONFIG_ENABLED     = 'payment/billsafe/active';
    const XML_CONFIG_STATUS_PAID = 'payment/billsafe/order_status';
    const MODULE_CONFIG          = 'Netresearch_Billsafe';

    /**
     * @param $order Mage_Sales_Model_Order
     *
     * @return Mage_Sales_Model_Order
     */
    public function manipulateOrderWithPaymentData($order)
    {
        $paymentBillsafe = Mage::getModel('billsafe/payment');
        $order->getPayment()->setMethod($paymentBillsafe->getCode());
        $paymentBillsafe->setInfoInstance($order->getPayment());
        $order->getPayment()->setMethodInstance($paymentBillsafe);
        $order->save();
        $orderObject = new Varien_Object(array('increment_id' => $this->getShopgateOrder()->getOrderNumber()));
        $data        = Mage::getSingleton('billsafe/client')->getPaymentInstruction($orderObject);
        if ($data) {
            $order->getPayment()->setAdditionalInformation(
                'BillsafeStatus',
                Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_ACTIVE
            );
            $order->getPayment()->setAdditionalInformation('Recipient', $data->recipient);
            $order->getPayment()->setAdditionalInformation('BankCode', $data->bankCode);
            $order->getPayment()->setAdditionalInformation('AccountNumber', $data->accountNumber);
            $order->getPayment()->setAdditionalInformation('BankName', $data->bankName);
            $order->getPayment()->setAdditionalInformation('Bic', $data->bic);
            $order->getPayment()->setAdditionalInformation('Iban', $data->iban);
            $order->getPayment()->setAdditionalInformation('Reference', $data->reference);
            $order->getPayment()->setAdditionalInformation('Amount', $data->amount);
            $order->getPayment()->setAdditionalInformation('CurrencyCode', $data->currencyCode);
            $order->getPayment()->setAdditionalInformation('Note', $data->note);
            $order->getPayment()->setAdditionalInformation('legalNote', $data->legalNote);
        } else {
            $order->getPayment()->setAdditionalInformation(
                'BillsafeStatus',
                Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED
            );
        }
        $paymentInfos = $this->getShopgateOrder()->getPaymentInfos();
        $orderTrans   = Mage::getModel('sales/order_payment_transaction');
        $orderTrans->setOrderPaymentObject($order->getPayment());
        $orderTrans->setIsClosed(false);
        $orderTrans->setTxnId($paymentInfos['billsafe_transaction_id']);
        $orderTrans->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $orderTrans->save();
        $order->getPayment()->importTransactionInfo($orderTrans);
        $order->getPayment()->setDataChanges(true);

        return $order;
    }
}