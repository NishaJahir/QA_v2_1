<?php
/**
 * This file is used for defining Novalnet constant values
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
 
namespace Novalnet\Constants;

/**
 * Class NovalnetConstants
 * @package Novalnet\Constants
 */
class NovalnetConstants
{
    const PLUGIN_VERSION = '7.0.0-NN(12.0.0)';
    const PAYMENT_URL    = 'https://payport.novalnet.de/v2/payment';
    const TX_DETAILS_UPDATE_URL = 'https://payport.novalnet.de/v2/transaction/details';
    const PAYMENT_AUTHORIZE_URL = 'https://payport.novalnet.de/v2/authorize';
}
