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

namespace Novalnet\Providers;

use \Plenty\Modules\Authorization\Services\AuthHelper;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\Templates\Twig;

/**
 * Class NovalnetOrderConfirmationDataProvider
 * @package Novalnet\Providers
 */
class NovalnetOrderConfirmationDataProvider
{
    /**
     * Setup the Novalnet transaction comments for the requested order
     *
     * @param Twig $twig
     * @param Arguments $arg
     * 
     * @return array
     */
    public function call(Twig $twig, $arg)
    {
        $paymentService = pluginApp(PaymentService::class);
        $order = $arg[0];
        if (!empty ($order['id'])) {
            $transactionCommentVal = $paymentService->getTransactionCommentVal($order['id']);
            return $twig->render('Novalnet::NovalnetOrderHistory', ['bankDetails' => $transactionCommentVal['bankDetails'], 'transactionDetails' => $transactionCommentVal['transactionDetails']]);
        }
    }
}

    

