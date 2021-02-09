<?php
/**
 * This file is used for creating and updating Novalnet payment methods
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
 
namespace Novalnet\Migrations;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Novalnet\Helper\PaymentHelper;
use Plenty\Plugin\Log\Loggable;
/**
 * Class UpgradePaymentMethods
 * @package Novalnet\Migrations
 */
class CreateAndUpdatePaymentMethodsNew
{
   use Loggable;
    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * CreatePaymentMethod constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepository,
                                PaymentHelper $paymentHelper)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Run on plugin build
     *
     * Create Method of Payment ID for Novalnet payment if they don't exist
     */
    public function run()
    {
        $this->createNovalnetPaymentMethodByPaymentKey('NOVALNET_SEPA', 'Novalnet Direct Debit SEPA');
        $this->createNovalnetPaymentMethodByPaymentKey('NOVALNET_CC', 'Novalnet Credit/Debit Cards');
        $this->createNovalnetPaymentMethodByPaymentKey('NOVALNET_INVOICE', 'Novalnet Invoice');
        $this->createNovalnetPaymentMethodByPaymentKey('NOVALNET_PAYPAL', 'Novalnet PayPal');
        $this->createNovalnetPaymentMethodByPaymentKey('NOVALNET_INSTALMENT_INVOICE', 'Novalnet Instalment by Invoice');
        $this->getLogger(__METHOD__)->error('create payment Name', 'create');
    }

    /**
     * Create and update payment method with given parameters if it doesn't exist
     *
     * @param string $paymentKey
     * @param string $paymentName
     */
    private function createNovalnetPaymentMethodByPaymentKey($paymentKey, $paymentName)
    {
        $payment_data = $this->paymentHelper->getPaymentMethodByKey($paymentKey);
        if ($payment_data == 'no_paymentmethod_found')
        {
            $paymentMethodData = ['pluginKey'  => 'plenty_novalnet',
                                'paymentKey' => $paymentKey,
                                'paymentName' => $paymentName
                               ];
            $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
        } elseif ($payment_data[1] == $paymentKey && !in_array ($payment_data[2], ['Novalnet Credit/Debit Cards', 'Novalnet Direct Debit SEPA', 'Novalnet Invoice', 'Novalnet PayPal']) ) {
            $paymentMethodData = ['pluginKey'  => 'Novalnet',
                                'paymentKey' => $paymentKey,
                                'paymentName' => $paymentName,
                                'id' => $payment_data[0]
                               ];
            $this->getLogger(__METHOD__)->error('update payment Name', $paymentMethodData);
            $this->paymentMethodRepository->updateName($paymentMethodData);
        }
    }
}
