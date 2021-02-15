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

use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Novalnet\Helper\PaymentHelper;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Frontend\Services\AccountService;
use Novalnet\Constants\NovalnetConstants;
use Novalnet\Services\TransactionService;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;
use Novalnet\Models\TransactionLog;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Payment\History\Contracts\PaymentHistoryRepositoryContract;
use Plenty\Modules\Payment\History\Models\PaymentHistory as PaymentHistoryModel;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract; 

/**
 * Class PaymentService
 * @package Novalnet\Services
 */
class PaymentService
{
    use Loggable;
    
    /**
     * @var PaymentHistoryRepositoryContract
     */
    private $paymentHistoryRepo;
    
   /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var ConfigRepository
     */
    private $config;
   
    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $sessionStorage;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;

    /**
     * @var CountryRepositoryContract
     */
    private $countryRepository;

    /**
     * @var WebstoreHelper
     */
    private $webstoreHelper;
    
    /**
     * @var Twig
     */
    private $twig;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    
    /**
     * @var TransactionLogData
     */
    private $transactionLogData;
    
    /**
     * @var LibraryCallContract
     */
    private $libCall;

    /**
     * PaymentService Constructor.
     *
     * @param ConfigRepository $config
     * @param FrontendSessionStorageFactoryContract $sessionStorage
     * @param AddressRepositoryContract $addressRepository
     * @param CountryRepositoryContract $countryRepository
     * @param WebstoreHelper $webstoreHelper
     * @param PaymentHelper $paymentHelper
     * @param TransactionService $transactionLogData
     * @param PaymentHistoryRepositoryContract $paymentHistoryRepo
     * @param PaymentRepositoryContract $paymentRepository
     * @param Twig $twig
     * @param TransactionService $transactionLogData
     * @param LibraryCallContract $libCall
     */
    public function __construct(ConfigRepository $config,
                                FrontendSessionStorageFactoryContract $sessionStorage,
                                AddressRepositoryContract $addressRepository,
                                CountryRepositoryContract $countryRepository,
                                WebstoreHelper $webstoreHelper,
                                PaymentHelper $paymentHelper,
                                PaymentHistoryRepositoryContract $paymentHistoryRepo,
                                PaymentRepositoryContract $paymentRepository,
                                Twig $twig,
                                TransactionService $transactionLogData,
                                LibraryCallContract $libCall)
    {
        $this->config                   = $config;
        $this->sessionStorage           = $sessionStorage;
        $this->addressRepository        = $addressRepository;
        $this->countryRepository        = $countryRepository;
        $this->webstoreHelper           = $webstoreHelper;
        $this->paymentHistoryRepo       = $paymentHistoryRepo;
        $this->paymentRepository        = $paymentRepository;
        $this->paymentHelper            = $paymentHelper;
        $this->twig                     = $twig;
        $this->transactionLogData       = $transactionLogData;
        $this->libCall = $libCall;
    }
    
    /**
    * Check the payment activate conditions
    * 
    * @param string $paymentKey
    * return bool
    */
    public function isPaymentActive($basket, $paymentKey) 
    {
        $paymentDisplay = false;
        
        // Allowed country check
        $paymentAllowedCountry = 'true';
        if ($allowedCountry = $this->config->get('Novalnet.' . $paymentKey . '_allowed_country')) {
            $paymentAllowedCountry  = $this->allowedCountries($basket, $allowedCountry);
        }

        // Minimum order amount check
        $minimumOrderAmount = 'true';
        $minOrderAmount = trim($this->config->get('Novalnet.' . $paymentKey . '_minimum_order_amount'));
        if (!empty($minOrderAmount) && is_numeric($minOrderAmount)) {
            $minimumOrderAmount = $this->getMinBasketAmount($basket, $minOrderAmount);
        }

        // Maximum order amount check
        $maximumOrderAmount = 'true';
        $maxOrderAmount = trim($this->config->get('Novalnet.' . $paymentKey . '_maximum_order_amount'));
        if (!empty($maxOrderAmount) && is_numeric($maxOrderAmount)) {
            $maximumOrderAmount = $this->getMaxBasketAmount($basket, $maxOrderAmount);
        }

        if (!empty(trim($this->config->get('Novalnet.novalnet_public_key'))) && is_numeric(trim($this->config->get('Novalnet.novalnet_tariff_id'))) && !empty(trim($this->config->get('Novalnet.novalnet_access_key'))) && $paymentAllowedCountry && $minimumOrderAmount && $maximumOrderAmount)
        {
            $paymentDisplay = true;
        }
        return $paymentDisplay;
    }
    
    /**
     * Show payment for allowed countries
     *
     * @param object $basket
     * @param string $allowed_country
     *
     * @return bool
     */
    public function allowedCountries(Basket $basket, $allowed_country) 
    {
        $allowed_country = str_replace(' ', '', strtoupper($allowed_country));
        $allowed_country_array = explode(',', $allowed_country);    
        try {
            if (! is_null($basket) && $basket instanceof Basket && !empty($basket->customerInvoiceAddressId)) {         
                $billingAddressId = $basket->customerInvoiceAddressId;              
                $address = $this->addressRepository->findAddressById($billingAddressId);
                $country = $this->countryRepository->findIsoCode($address->countryId, 'iso_code_2');
                if(!empty($address) && !empty($country) && in_array($country,$allowed_country_array)) {             
                    return true;
                }
        
            }
        } catch(\Exception $e) {
            return false;
        }
        return false;
    }
    
    /**
     * Show payment for Minimum Order Amount
     *
     * @param object $basket
     * @param int $minimum_amount
     *
     * @return bool
     */
    public function getMinBasketAmount(Basket $basket, $minimum_amount) 
    {   
        if (!is_null($basket) && $basket instanceof Basket) {
            $amount = $this->paymentHelper->convertAmountToSmallerUnit($basket->basketAmount);
            if (!empty($minimum_amount) && $minimum_amount<=$amount)    {
                return true;
            }
        } 
        return false;
    }
    
    /**
     * Show payment for Maximum Order Amount
     *
     * @param object $basket
     * @param int $maximum_amount
     *
     * @return bool
     */
    public function getMaxBasketAmount(Basket $basket, $maximum_amount) 
    {   
        if (!is_null($basket) && $basket instanceof Basket) {
            $amount = $this->paymentHelper->convertAmountToSmallerUnit($basket->basketAmount);
            if (!empty($maximum_amount) && $maximum_amount>=$amount)    {
            
                return true;
            }
        } 
        return false;
    }
    
    /**
    * Check the Payment display conditions - Instalment invoice
    *
    * @param object $basket
    * @param string $paymentKey
    * 
    * @return bool
    */
    public function checkPaymentDisplayConditions(Basket $basket, $paymentKey) 
    {
        try {
            if (! is_null($basket) && $basket instanceof Basket && !empty($basket->customerInvoiceAddressId)) {
            $paymentActive = $this->config->get('Novalnet.'.$paymentKey.'_payment_active');
            if ($paymentActive == 'true') {
                // Minimum amount validation
                $minimumAmount = trim($this->config->get('Novalnet.'.$paymentKey. '_min_amount'));
                $minimumAmount = ((preg_match('/^[0-9]*$/', $minimumAmount) && $minimumAmount >= '1998')  ? $minimumAmount : '1998');
                $amount        = (sprintf('%0.2f', $basket->basketAmount) * 100);
                // Check instalment cycles
                $instalementCyclesCheck = false;
                $instalementCycles = explode(',', $this->config->get('Novalnet.' .$paymentKey . '_cycles'));
                if($minimumAmount >= 1998) {
                    foreach($instalementCycles as $key => $value) {
                        $cycleAmount = ($amount / $value);
                        if($cycleAmount >= 999) {
                            $instalementCyclesCheck = true;
                        }
                    }
                }
                // Address validation
                $billingAddressId = $basket->customerInvoiceAddressId;
                $billingAddress = $this->addressRepository->findAddressById($billingAddressId);
                $shippingAddress = $billingAddress;
                if(!empty($basket->customerShippingAddressId)){
                    $shippingAddress = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
                }
                // Get country validation value
                $billingShippingDetails = $this->getBillingShippingDetails($billingAddress, $shippingAddress);
                $countryValidation = $this->europeanUnionCountryValidation($paymentKey, $billingShippingDetails['billing']['country_code']);
                // Check the payment condition
                if((((int) $amount >= (int) $minimumAmount && $instalementCyclesCheck && $countryValidation && $basket->currency == 'EUR' && ($billingShippingDetails['billing'] === $billingShippingDetails['shipping']) )
                )) {
                    return true;
                } else {
                    return false;
                }
            }
           }
       } catch(\Exception $e) {
        $this->getLogger(__METHOD__)->error('catch error', $basket);
            return false;
        }
        return false;
     
    }
    
   /**
    * Check if the customer from EU country or not
    *
    * @param string $paymentKey
    * @param string $countryCode
    * 
    * @return bool
    */
    public function europeanUnionCountryValidation($paymentKey, $countryCode) 
    {
        $allowB2B = $this->config->get('Novalnet.' . $paymentKey . '_allow_b2b_customer');
        $europeanUnionCountryCodes =  [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR',
            'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL',
            'PT', 'RO', 'SE', 'SI', 'SK', 'UK', 'CH'
        ];
        if(in_array($countryCode, ['DE', 'AT', 'CH'])) {
            $countryValidation = true;
        } elseif($allowB2B == true && in_array($countryCode, $europeanUnionCountryCodes)) {
            $countryValidation = true;
        } else {
            $countryValidation = false;
        }
        return $countryValidation;
    }
    
    /**
     * Form customer billing and shipping details
     *
     * @param object $billingAddress
     * @param object $shippingAddress
     *
     * @return array
     */
    public function getBillingShippingDetails($billingAddress, $shippingAddress) 
    {
        $billingShippingDetails = [];
        $billingShippingDetails['billing']     = [
                'street'       => $billingAddress->street,
                'house_no'     => $billingAddress->houseNumber,
                'city'         => $billingAddress->town,
                'zip'          => $billingAddress->postalCode,
                'country_code' => strtoupper($this->countryRepository->findIsoCode($billingAddress->countryId, 'iso_code_2'))
            ];
         $billingShippingDetails['shipping']    = [
                'street'   => $shippingAddress->street,
                'house_no'     => $shippingAddress->houseNumber,
                'city'     => $shippingAddress->town,
                'zip' => $shippingAddress->postalCode,
                'country_code' => strtoupper($this->countryRepository->findIsoCode($shippingAddress->countryId, 'iso_code_2'))
            ];
        return $billingShippingDetails;
    }
    
    /**
     * Build Novalnet payment request parameters
     *
     * @param object $basket
     * @param string $paymentKey
     *
     * @return array
     */
    public function getPaymentRequestParameters(Basket $basket, $paymentKey = '') 
    {
        
        /** @var \Plenty\Modules\Frontend\Services\VatService $vatService */
        $vatService = pluginApp(\Plenty\Modules\Frontend\Services\VatService::class);

        //we have to manipulate the basket because its stupid and doesnt know if its netto or gross
        if(!count($vatService->getCurrentTotalVats())) {
            $basket->itemSum = $basket->itemSumNet;
            $basket->shippingAmount = $basket->shippingAmountNet;
            $basket->basketAmount = $basket->basketAmountNet;
        }
        
        $billingAddressId = $basket->customerInvoiceAddressId;
        $billingAddress = $this->addressRepository->findAddressById($billingAddressId);
        $shippingAddress = $billingAddress;
        if(!empty($basket->customerShippingAddressId)){
            $shippingAddress = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
        }
        $customerName = $this->getCustomerName($billingAddress);

        $account = pluginApp(AccountService::class);
        $customerId = $account->getAccountContactId();
        $paymentKeyLower = strtolower((string) $paymentKey);
        $testModeKey = 'Novalnet.' . $paymentKeyLower . '_test_mode'; 

        $paymentRequestParameters = [];
        // Build Merchant Data
        $paymentRequestParameters['merchant'] = [
            'signature' => trim($this->config->get('Novalnet.novalnet_public_key')),
            'tariff'    => trim($this->config->get('Novalnet.novalnet_tariff_id')),
        ];

        // Build Customer Data
        $paymentRequestParameters['customer'] = [
            'first_name' => !empty($billingAddress->firstName) ? $billingAddress->firstName : $customerName['firstName'],
            'last_name'  => !empty($billingAddress->lastName) ? $billingAddress->lastName : $customerName['lastName'],
            'email'      => $billingAddress->email,
            'gender'     => (!empty($billingAddress->gender) ? ($billingAddress->gender == 'male' ? 'm' : ($billingAddress->gender == 'female' ? 'f' : 'u' ) ) : 'u' ),
            'customer_no'  => ($customerId) ?? 'guest',
            'customer_ip'  => $this->paymentHelper->getRemoteAddress(),
        ];
        
        $billingShippingDetails = $this->getBillingShippingDetails($billingAddress, $shippingAddress);
        $paymentRequestParameters['customer'] = array_merge($paymentRequestParameters['customer'], $billingShippingDetails);
        
        if ($paymentRequestParameters['customer']['billing'] == $paymentRequestParameters['customer']['shipping']) {
            $paymentRequestParameters['customer']['shipping']['same_as_billing'] = '1';
        }

        // Build Transaction Data
        $paymentRequestParameters['transaction'] = [
            'test_mode'        => (int)($this->config->get($testModeKey) == 'true'),
            'payment_type'     => $this->getTypeByPaymentKey($paymentKey),
            'amount'           => $this->paymentHelper->convertAmountToSmallerUnit($basket->basketAmount),
            'currency'         => $basket->currency,
            'hook_url'         => $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/callback/',
        ];
        
        // Build optional Data
        $paymentRequestParameters['custom'] = [
            'lang' => strtoupper($this->sessionStorage->getLocaleSettings()->language),
        ];

        if(!empty($billingAddress->companyName)) {
            $paymentRequestParameters['customer']['billing']['company'] = $billingAddress->companyName;
        } elseif(!empty($shippingAddress->companyName)) {
            $paymentRequestParameters['customer']['shipping']['company'] = $shippingAddress->companyName;
        }

        if(!empty($billingAddress->phone)) {
            $paymentRequestParameters['tel'] = $billingAddress->phone;
        }
    
        $onHoldPaymentUrl = $this->getAdditionalPaymentData($paymentKey, $paymentRequestParameters);
        
        $url = !empty($onHoldPaymentUrl) ? $onHoldPaymentUrl : NovalnetConstants::PAYMENT_URL;
        
        return [
            'data' => $paymentRequestParameters,
            'url'  => $url
        ];
    }
    
    /**
     * Get customer name if the salutation as Person
     *
     * @param object $address
     *
     * @return array
     */
    public function getCustomerName($address) 
    {
        foreach ($address->options as $option) {
            if ($option->typeId == 12) {
                    $name = $option->value;
            }
        }
        $customerName = explode(' ', $name);
        $firstname = $customerName[0];
            if( count( $customerName ) > 1 ) {
                unset($customerName[0]);
                $lastname = implode(' ', $customerName);
            } else {
                $lastname = $firstname;
            }
        $firstName = empty ($firstname) ? $lastname : $firstname;
        $lastName = empty ($lastname) ? $firstname : $lastname;
        return ['firstName' => $firstName, 'lastName' => $lastName];
    }
    
    /**
    * Get payment type by plenty payment Key
    *
    * @param string $paymentKey
    * 
    * @return string
    */
    public function getTypeByPaymentKey($paymentKey) 
    {
        $payment = [
            'NOVALNET_INVOICE'=>'INVOICE',
            'NOVALNET_CC'=>'CREDITCARD',
            'NOVALNET_SEPA'=>'DIRECT_DEBIT_SEPA',
            'NOVALNET_PAYPAL'=>'PAYPAL',
            'NOVALNET_INSTALMENT_INVOICE' => 'INSTALMENT_INVOICE'
        ];

        return $payment[$paymentKey];
    }
    
    /**
     * Get additional required payment data
     *
     * @param string $paymentKey
     * @param array $paymentRequestParameters
     * 
     * @return string|null
     */
    public function getAdditionalPaymentData($paymentKey, &$paymentRequestParameters) 
    {
        $onHoldPaymentUrl = '';
        if (in_array($paymentKey, ['NOVALNET_CC', 'NOVALNET_SEPA', 'NOVALNET_INVOICE', 'NOVALNET_INSTALMENT_INVOICE', 'NOVALNET_PAYPAL'])) {
            $onHoldLimit = $this->config->get('Novalnet.' . strtolower($paymentKey) . '_on_hold');
            $onHoldAuthorize = $this->config->get('Novalnet.' . strtolower($paymentKey) . '_payment_action');
            if ((is_numeric($onHoldLimit) && $paymentRequestParameters['amount'] >= $onHoldLimit && $onHoldAuthorize == 'true') || ($onHoldAuthorize == 'true' && empty($onHoldLimit))) {
                $onHoldPaymentUrl = NovalnetConstants::PAYMENT_AUTHORIZE_URL;
            }
        }
        if ($paymentKey == 'NOVALNET_SEPA') {
            $sepaDueDate = trim($this->config->get('Novalnet.novalnet_sepa_due_date'));
            if(is_numeric($sepaDueDate) && $sepaDueDate >= 2 && $sepaDueDate <= 14) {
                $paymentRequestParameters['transaction']['sepa_due_date'] = $this->paymentHelper->getPaymentDueDate($sepaDueDate);
            }
        } elseif ($paymentKey == 'NOVALNET_INVOICE') {
            $invoiceDueDate = trim($this->config->get('Novalnet.novalnet_invoice_due_date'));
            if(is_numeric($invoiceDueDate)) {
                $paymentRequestParameters['transaction']['due_date'] = $this->paymentHelper->getPaymentDueDate($invoiceDueDate);
            }
        } elseif ($paymentKey == 'NOVALNET_PAYPAL') {
            $paymentRequestParameters['transaction']['return_url'] = $paymentRequestParameters['transaction']['error_return_url'] = $this->getReturnPageUrl();
        }
        return $onHoldPaymentUrl;
    }
    
    /**
     * Get required data for credit card form load
     *
     * @param object $basket
     * @param string $paymentKey
     * 
     * @return string
     */
    public function getCcFormData(Basket $basket, $paymentKey) {
        $billingAddressId = $basket->customerInvoiceAddressId;
        $billingAddress = $this->addressRepository->findAddressById($billingAddressId);
        if(!empty($basket->customerShippingAddressId)){
            $shippingAddress = $this->addressRepository->findAddressById($basket->customerShippingAddressId);
        }
        $customerName = $this->getCustomerName($billingAddress);
        $ccFormRequestParameters = [
            'client_key'    => trim($this->config->get('Novalnet.novalnet_client_key')),
            'inline_form'   => (int) ($this->config->get('Novalnet.novalnet_cc_display_inline_form') == 'true'),
            'test_mode'     => (int)($this->config->get('Novalnet.' . strtolower((string) $paymentKey) . '_test_mode') == 'true'),
            'first_name'    => !empty($billingAddress->firstName) ? $billingAddress->firstName : $customerName['firstName'],
            'last_name'     => !empty($billingAddress->lastName) ? $billingAddress->lastName : $customerName['lastName'],
            'email'         => $billingAddress->email,
            'street'        => $billingAddress->street,
            'house_no'      => $billingAddress->houseNumber,
            'city'          => $billingAddress->town,
            'zip'           => $billingAddress->postalCode,
            'country_code'  => $this->countryRepository->findIsoCode($billingAddress->countryId, 'iso_code_2'),
            'amount'        => $this->paymentHelper->convertAmountToSmallerUnit($basket->basketAmount),
            'currency'      => $basket->currency,
            'lang'          => strtoupper($this->sessionStorage->getLocaleSettings()->language)
        ];  
        $billingShippingDetails = $this->getBillingShippingDetails($billingAddress, $shippingAddress);
        if ($billingShippingDetails['billing'] == $billingShippingDetails['shipping']) {
            $ccFormRequestParameters['same_as_billing'] = 1;
        }
        return json_encode($ccFormRequestParameters);
    }
    
    /**
     * Retrieves Credit Card form style set in payment configuration and texts present in language files
     *
     * @return string
     */
    public function getCcFormFields() 
    {
        $ccformFields = [];

        $styleConfiguration = array('novalnet_cc_standard_style_label', 'novalnet_cc_standard_style_field', 'novalnet_cc_standard_style_css');

        foreach ($styleConfiguration as $value) {
            $ccformFields[$value] = trim($this->config->get('Novalnet.' . $value));
        }

        $textFields = array( 'novalnetCcHolderLabel', 'novalnetCcHolderInput', 'novalnetCcNumberLabel', 'novalnetCcNumberInput', 'novalnetCcExpiryDateLabel', 'novalnetCcExpiryDateInput', 'novalnetCcCvcLabel', 'novalnetCcCvcInput', 'novalnetCcError' );

        foreach ($textFields as $value) {
            $ccformFields[$value] = utf8_encode($this->paymentHelper->getTranslatedText($value));
        }
        return json_encode($ccformFields);
    }
    
    /**
     * Get the payment response controller URL
     *
     * @return string
     */
    private function getReturnPageUrl() 
    {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/paymentResponse/';
    }

    /**
    * Get the direct payment process controller URL
    *
    * @return string
    */
    public function getProcessPaymentUrl() 
    {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/processPayment/';
    }

    /**
    * Get the payment details removal process controller URL
    *
    * @return string
    */
    public function getSavedTokenRemovalUrl() {
        return $this->webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl . '/payment/novalnet/removePaymentDetails/';
    }
    
    /**
     * Send the payment call to novalnet server
     *
     */
    public function performServerCall() 
    {
        try {
            $serverRequestData = $this->sessionStorage->getPlugin()->getValue('nnPaymentData');
            $serverRequestData['data']['transaction']['order_no'] = $this->sessionStorage->getPlugin()->getValue('nnOrderNo');
            $response = $this->libCall->call(
                'Novalnet::guzzle_client',
                ['nn_access_key' => trim($this->config->get('Novalnet.novalnet_access_key')), 'nn_request' => $serverRequestData['data'], 'nn_request_process_url' => $serverRequestData['url']] 
            );
             if($serverRequestData['data']['transaction']['payment_type'] == 'PAYPAL') {
                 if (!empty($response['result']['redirect_url']) && !empty($response['transaction']['txn_secret'])) {
                        header('Location: ' . $response['result']['redirect_url']);
                        exit;
                 }
             } else {
                $notificationMessage = $this->paymentHelper->getTranslatedText('paymentSuccess');
                $isPaymentSuccess = isset($response['result']['status']) && $response['result']['status'] == 'SUCCESS';
                if($isPaymentSuccess)
                {           
                    if(isset($serverRequestData['data']['transaction']['payment_data']['pan_hash']))
                    {
                       unset($serverRequestData['data']['transaction']['payment_data']['pan_hash']);
                    }
                    $this->sessionStorage->getPlugin()->setValue('nnPaymentData', array_merge($serverRequestData, $response));
                    $this->pushNotification($notificationMessage, 'success', 100);
                } else {
                    $this->pushNotification($notificationMessage, 'error', 100);
                }
            }
        } catch (\Exception $e) {
                $this->getLogger(__METHOD__)->error('Novalnet::performServerCall', $e);
        }
    }
    
    /**
     * Display notification message for success and failure transaction
     *
     * @param string $message
     * @param string $type
     * @param int $code
     */
    public function pushNotification($message, $type, $code = 0) 
    {
        $notifications = json_decode($this->sessionStorage->getPlugin()->getValue('notifications'), true);  
        $notification = [
                'message'       => $message,
                'code'          => $code,
                'stackTrace'    => []
               ];
        $lastNotification = $notifications[$type];
        if( !is_null($lastNotification) ) {
            $notification['stackTrace'] = $lastNotification['stackTrace'];
            $lastNotification['stackTrace'] = [];
            array_push( $notification['stackTrace'], $lastNotification );
        }
        $notifications[$type] = $notification;
        $this->sessionStorage->getPlugin()->setValue('notifications', json_encode($notifications));
    }
    
    /**
     * Validate the payment response data
     *
     */
    public function validatePaymentResponse() {
        try {
            $nnPaymentData = $this->sessionStorage->getPlugin()->getValue('nnPaymentData');
            $this->sessionStorage->getPlugin()->setValue('nnPaymentData', null);

            $nnPaymentData['mop']            = $this->sessionStorage->getPlugin()->getValue('mop');
            $this->getLogger(__METHOD__)->error('validate response',  $nnPaymentData);
            $nnPaymentData['payment_method'] = (!empty($nnPaymentData['mop'])) ? strtolower($this->paymentHelper->getPaymentKeyByMop($nnPaymentData['mop'])) : $nnPaymentData['payment_key'];
            if ($nnPaymentData['transaction']['payment_type'] == 'PAYPAL') {
             $nnPaymentData['payment_method'] = 'novalnet_paypal';
            }
            $additionalInfo = $this->additionalInfo($nnPaymentData);
        
            if($nnPaymentData['payment_method'] == 'INSTALMENT_INVOICE') {
                $instalmentInfo = [
                    'total_paid_amount' => $nnPaymentData['instalment']['cycle_amount'],
                    'instalment_cycle_amount' => $nnPaymentData['instalment']['cycle_amount'],
                    'paid_instalment' => $nnPaymentData['instalment']['cycles_executed'],
                    'due_instalment_cycles' => $nnPaymentData['instalment']['pending_cycles'],
                    'next_instalment_date' => $nnPaymentData['instalment']['next_cycle_date'],
                    'future_instalment_date' => $nnPaymentData['instalment']['cycle_dates']
                ];
            }

            $transactionData = [
                'amount'           => $nnPaymentData['transaction']['amount'],
                'callback_amount'  => $nnPaymentData['transaction']['amount'],
                'tid'              => $nnPaymentData['transaction']['tid'],
                'ref_tid'          => $nnPaymentData['transaction']['tid'],
                'payment_name'     => $nnPaymentData['payment_method'],
                'customer_email'  => $nnPaymentData['customer']['email'],
                'order_no'         => $nnPaymentData['transaction']['order_no'],
                'additional_info'  => !empty($additionalInfo) ? json_encode($additionalInfo) : 0,
                'save_card_token'   => !empty($nnPaymentData['transaction']['payment_data']['token']) ? $nnPaymentData['transaction']['payment_data']['token'] : 0,
                'mask_details'  => !empty($nnPaymentData['transaction']['payment_data']['token']) ? $this->saveAdditionalPaymentData ($nnPaymentData) : 0,
                'instalment_info'  => !empty($instalmentInfo) ? json_encode($instalmentInfo) : 0,
                ];
           
            if($nnPaymentData['payment_method'] == 'novalnet_invoice' || (in_array($nnPaymentData['transaction']['status'], ['PENIDNG', 'ON_HOLD']))) {
                $transactionData['callback_amount'] = 0;    
            }
            $this->transactionLogData->saveTransaction($transactionData);
            
            if(in_array($nnPaymentData['result']['status'], ['PENDING', 'SUCCESS'])) {
               $this->paymentHelper->createPlentyPayment($nnPaymentData);
            }
        } catch (\Exception $e) {
                $this->getLogger(__METHOD__)->error('Novalnet::validatePaymentResponse', $e);
        }
     }
     
    /**
    * Build the additional params
    *
    * @param array $nnPaymentData
    *
    * @return array
    */
    public function additionalInfo ($nnPaymentData) 
    {
         $lang = strtolower((string)$nnPaymentData['custom']['lang']);
         $additionalInfo = [
            'currency' => $nnPaymentData['transaction']['currency'],
            'plugin_version' => NovalnetConstants::PLUGIN_VERSION,
            'test_mode' => !empty($nnPaymentData['transaction']['test_mode']) ? $this->paymentHelper->getTranslatedText('testOrder',$lang) : 0,
         ];
        if($nnPaymentData['payment_method'] == 'novalnet_invoice') {
            if(!empty($nnPaymentData['transaction']['bank_details']) ) {
            $additionalInfo['invoice_bankname']  = $nnPaymentData['transaction']['bank_details']['bank_name'];
            $additionalInfo['invoice_bankplace'] = utf8_encode($nnPaymentData['transaction']['bank_details']['bank_place']);
            $additionalInfo['invoice_iban']     = $nnPaymentData['transaction']['bank_details']['iban'];
            $additionalInfo['invoice_bic']     = $nnPaymentData['transaction']['bank_details']['bic'];
            $additionalInfo['invoice_account_holder'] = $nnPaymentData['transaction']['bank_details']['account_holder'];
            }
            $additionalInfo['due_date']     = !empty($nnPaymentData['transaction']['due_date']) ? $nnPaymentData['transaction']['due_date'] : 0;
            $additionalInfo['invoice_type'] = !empty($nnPaymentData['transaction']['payment_type']) ? $nnPaymentData['transaction']['payment_type'] : 0;
            $additionalInfo['invoice_ref']  = !empty($nnPaymentData['transaction']['invoice_ref']) ? $nnPaymentData['transaction']['invoice_ref'] : 0;
        }
         return $additionalInfo;
    }
    
    /**
     * save the payment details for one-click shopping
     *
     * @param array $requestPaymentData
     *
     * @return array|null
     */
    public function saveAdditionalPaymentData($requestPaymentData) {
        switch (strtolower($requestPaymentData['payment_method'])) {
            case 'novalnet_cc':
                return json_encode([
                        'card_type' => $requestPaymentData['transaction']['payment_data']['card_brand'],
                        'card_number' => $requestPaymentData['transaction']['payment_data']['card_number'],
                        'card_validity' => $requestPaymentData['transaction']['payment_data']['card_expiry_month'] .'/'. $requestPaymentData['transaction']['payment_data']['card_expiry_year']
                        ]);
            case 'novalnet_sepa':
                return json_encode([
                        'iban' => $requestPaymentData['transaction']['payment_data']['iban']
                    ]);
            case 'novalnet_paypal':
                return json_encode([
                        'paypal_account' => utf8_decode($requestPaymentData['transaction']['payment_data']['paypal_account'])
                        ]);
            default:
                return '';
            }
    }
    
    /**
     * Get the transaction comments value
     *
     * @param int $orderId
     *
     * @return array|null
     */
    public function getTransactionCommentVal($orderId) 
    {
        if (!empty($orderId)) {
            $payments = $this->paymentRepository->getPaymentsByOrderId($orderId);
            foreach($payments as $payment)
            {
                $paymentProperties = $payment->properties;
                foreach($paymentProperties as $paymentProperty)
                {
                    if ($paymentProperty->typeId == 30)
                    {
                        $transactionStatus = $paymentProperty->value;
                    }
                }
                if($this->paymentHelper->getPaymentKeyByMop($payment->mopId))
                {
                    $orderId = (int) $payment->order['orderId'];
                    $transactionDetails = $this->getDatabaseValues($orderId);
                    $getTransactionDetails = $this->transactionLogData->getTransactionData('orderNo', $orderId);
                    $totalCallbackAmount = 0;
                    foreach ($getTransactionDetails as $transactionDetail) {
                       $totalCallbackAmount += $transactionDetail->callbackAmount;
                    }
                    if(in_array($transactionStatus, ['PENDING', 'ON_HOLD', 'SUCCESS']) && ( ($transactionDetails['invoice_type'] == 'INVOICE' && ($transactionDetail->amount > $totalCallbackAmount)) || $transactionDetails['paymentName'] == 'novalnet_instalment_invoice') ) {
                        $bankDetails = $transactionDetails;
                    }
                }
            }
            return ['bankDetails' => $bankDetails, 'transactionDetails' => $transactionDetails];
        }  
        return '';       
    }

    /**
     * Get database values
     *
     * @param int $orderId
     *
     * @return array
     */
    public function getDatabaseValues($orderId) 
    { 
        $database = pluginApp(DataBase::class);
        $transactionDetails = $database->query(TransactionLog::class)->where('orderNo', '=', $orderId)->get();
        if (!empty($transactionDetails)) {
            //Typecasting object to array
            $transactionDetails = (array)($transactionDetails[0]);
            $transactionDetails['order_no'] = $transactionDetails['orderNo'];
            $transactionDetails['amount'] = $transactionDetails['amount'] / 100;
            //Decoding the json as array
            $transactionDetails['additionalInfo'] = json_decode(  $transactionDetails['additionalInfo'], true );
            //Merging the array
            $origTransactionDetails = array_merge($transactionDetails, $transactionDetails['additionalInfo']);
            if (!empty($transactionDetails['instalmentInfo'])) {
                $transactionDetails['instalmentInfo'] = json_decode( $transactionDetails['instalmentInfo'], true );
                //Merging the array
                $transactionDetails = array_merge($transactionDetails, $transactionDetails['additionalInfo'], $transactionDetails['instalmentInfo']);
                //Unsetting the redundant key
                unset($transactionDetails['additionalInfo'], $transactionDetails['instalmentInfo']);
            } else {
                unset($transactionDetails['additionalInfo']);
            }
            return $origTransactionDetails;
        }
    }
    
    /**
     * Form the transaction comments
     *
     * @param array $txCommentsData
     *
     * @return string|null
     */
    public function formTransactionCommentsInvoicePDF($txCommentsData){
        $comments = '';
        if(!empty($txCommentsData['transactionDetails'])) {
            $comments .= PHP_EOL . PHP_EOL . $this->paymentHelper->getTranslatedText('novalnetDetails');
            $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('nnTid') . $txCommentsData['transactionDetails']['tid'];
            if(!empty($txCommentsData['transactionDetails']['test_mode'])) {
            $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('testOrder') . $txCommentsData['transactionDetails']['test_mode'];
            }
        }
        if(in_array($txCommentsData['transactionDetails']['paymentName'], ['NOVALNET_INVOICE', 'NOVALNET_INSTALMENT_INVOICE']) && !empty($txCommentsData['bankDetails'])) {
        $comments .= PHP_EOL . PHP_EOL . $this->paymentHelper->getTranslatedText('transferAmountText');
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText
        ('accountHolderNovalnet') . $txCommentsData['bankDetails']['invoice_account_holder'];
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('iban') . $txCommentsData['bankDetails']['invoice_iban'];
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('bic') . $txCommentsData['bankDetails']['invoice_bic'];
        if($txCommentsData['bankDetails']['due_date'])
        {
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('dueDate') . date('Y/m/d', (int)strtotime($txCommentsData['bankDetails']['due_date']));
        }
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('bank') . $txCommentsData['bankDetails']['invoice_bankname'];
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('amount') . $txCommentsData['bankDetails']['amount'] . ' ' . $txCommentsData['bankDetails']['currency'];

        $comments .= PHP_EOL . PHP_EOL .$this->paymentHelper->getTranslatedText('anyOneReferenceText');
        $comments .= PHP_EOL . $this->paymentHelper->getTranslatedText('paymentReference1').' ' .$txCommentsData['bankDetails']['invoice_ref']. PHP_EOL. $this->paymentHelper->getTranslatedText('paymentReference2') .' ' . 'TID '. $txCommentsData['bankDetails']['tid']. PHP_EOL;
        $comments .= PHP_EOL;
        }
        return $comments;
    }
    
    public function getNovalnetConfig($key)
    {
        return preg_replace('/\s+/', '', $this->config->get("Novalnet.$key"));
    }
}
