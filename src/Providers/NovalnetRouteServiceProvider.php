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

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class NovalnetRouteServiceProvider
 * @package Novalnet\Providers
 */
class NovalnetRouteServiceProvider extends RouteServiceProvider
{
    /**
     * Set route for success, failure process
     *
     * @param Router $router
     */
    public function map(Router $router)
    {
        // Get the additional requested URL's for the Novalnet payment process
        $router->post('payment/novalnet/processPayment', 'Novalnet\Controllers\PaymentController@processPayment');
        $router->get('payment/novalnet/paymentResponse', 'Novalnet\Controllers\PaymentController@paymentResponse');
        $router->post('payment/novalnet/removePaymentDetails', 'Novalnet\Controllers\PaymentController@removePaymentDetails');
        $router->match(['post', 'get'], 'payment/novalnet/callback', 'Novalnet\Controllers\WebhookController@processCallback');
    }
}
