<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

namespace Novalnet\Services;

use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;
use Novalnet\Models\TransactionLog;
use Plenty\Plugin\Log\Loggable;
use Novalnet\Services\PaymentService;

/**
 * Class TransactionService
 * @package Novalnet\Services
 */
class TransactionService
{
    use Loggable;

    /**
     * Save data in transaction table
     *
     * @param $transactionData
     */
    public function saveTransaction($transactionData) 
    {
        try {
            $database = pluginApp(DataBase::class);
            $transaction = pluginApp(TransactionLog::class);
            $transaction->orderNo             = $transactionData['order_no'];
            $transaction->amount              = $transactionData['amount'];
            $transaction->callbackAmount      = $transactionData['callback_amount'];
            $transaction->referenceTid        = $transactionData['ref_tid'];
            $transaction->transactionDatetime = date('Y-m-d H:i:s');
            $transaction->tid                 = $transactionData['tid'];
            $transaction->paymentName         = $transactionData['payment_name'];
            $transaction->customerEmail       = $transactionData['customer_email'];
            $transaction->additionalInfo      = !empty($transactionData['additional_info']) ? $transactionData['additional_info'] : null;
            $transaction->saveOneTimeToken    = !empty($transactionData['save_card_token']) ? $transactionData['save_card_token'] : "";
            $transaction->maskingDetails      = !empty($transactionData['mask_details']) ? $transactionData['mask_details'] : null;
            $transaction->instalmentInfo      = !empty($transactionData['instalment_info']) ? $transactionData['instalment_info'] : null;
            $this->getLogger(__METHOD__)->error('db save',  $transaction);
            $database->save($transaction);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('Novalnet transaction table insert failed!.', $e);
        }
    }

    /**
     * Retrieve transaction log table data
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    public function getTransactionData($key, $value) 
    {
        $database = pluginApp(DataBase::class);
        $order    = $database->query(TransactionLog::class)->where($key, '=', $value)->get();
        return $order;
    }
    
    /**
     * Delete payment token from the database table
     *
     * @param string $key
     * @param array $requestData
     */
    public function removeSavedPaymentDetails($key, $requestData) 
    {
        try {
            $database = pluginApp(DataBase::class);
            $orderDetails = $database->query(TransactionLog::class)->where($key, '=', $requestData['token'])->get();
            $orderDetail = $orderDetails[0];
            $orderDetail->saveOneTimeToken = "";
            $orderDetail->maskingDetails = null;
            $database->save($orderDetail);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('Removal of payment token failed!.', $e);
        }
    }
}
