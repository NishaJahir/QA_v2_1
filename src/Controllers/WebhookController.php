<?php
/**
 * This file is used for synchronize with Novalnet to shopsystem
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
 
namespace Novalnet\Controllers;

use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;
use Novalnet\Services\TransactionService;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;

/**
 * Class WebhookController
 * @package Novalnet\Controllers
 */
 
class WebhookController extends Controller
{
	use Loggable;
	
	/**
     * @var eventData
     */
    protected $eventData = [];

    /**
     * @var eventType
     */
    protected $eventType;

    /**
     * @var eventTid
     */
    protected $eventTid;
        
    /**
     * @var parentTid
     */
    protected $parentTid;
    
    /**
     * @var ipAllowed
     * @IP-ADDRESS Novalnet IP, is a fixed value, DO NOT CHANGE!!!!!
     */
    protected $ipAllowed = ['195.143.189.210', '195.143.189.214'];
    
    /**
     * @var twig
     */
    private $twig;
    
    /**
     * @var transaction
     */
    private $transaction;
    
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    
    /**
     * @var paymentService
     */
    private $paymentService;
    
    /**
     * @var orderRepository
     */
    private $orderRepository;
    
    /**
     * @var paymentRepository
     */
    private $paymentRepository;
	
	/**
     * WebhookController constructor.
     * 
     * @param Twig $twig
     * @param TransactionService $tranactionService
     */
    public function __construct(Twig $twig,
								TransactionService $tranactionService,
								PaymentHelper $paymentHelper,
								PaymentService $paymentService,
								OrderRepositoryContract $orderRepository,
								PaymentRepositoryContract $paymentRepository)
    {
		$this->twig                 = $twig;
		$this->transaction          = $tranactionService;
		$this->paymentHelper        = $paymentHelper;
		$this->paymentService       = $paymentService;
		$this->orderRepository      = $orderRepository;
		$this->paymentRepository    = $paymentRepository;
    }
    
    /**
     * Execute webhook process for the payments
     *
     */
	public function processCallback()
    {
		try {
            $this->eventData = json_decode(file_get_contents('php://input'), true);
        } catch (\Exception $e) {
            $this->getLogger(__METHOD__)->error('Received data is not in the JSON format', $e);
            return false;
        }
        
        // Mandatory webhook params
        $this->requiredParams = [
                    'event' => [ 'type', 'checksum', 'tid'],
                    'result' => ['status']
					];
		// validated the IP Address
		$this->validateIpAddress();
		
		// Validates the webhook params before processing
        $this->validateEventParams($this->requiredParams);
        
        // Set Event data
        $this->eventType = $this->eventData['event']['type'];
        $this->parentTid = ! empty($this->eventData['event']['parent_tid']) ? $this->eventData['event']['parent_tid'] :$this->eventData['event']['tid'];
        $this->eventTid  = $this->eventData['event']['tid'];
        
        // Get order details
        $transactionHistoryDetails = $this->getOrderReference();
		
		// If Order details missing render the mapping failed
		if(is_string($transactionHistoryDetails))
		{
			$this->renderTemplate($transactionHistoryDetails);
		}
        $orderObj = $this->orderObject($transactionHistoryDetails->orderNo);
        $orderLanguage= $this->orderLanguage($orderObj);
        // Get previous payment status
        $paymentStatus = $this->getPreviousPaymentStatus($transactionHistoryDetails->orderNo);
        switch ($this->eventType) {
			case 'PAYMENT':
				// Handle Initial PAYMENT notification (incl. communication failure, Authorization).
				$this->renderTemplate('The webhook notification received' . ($this->eventType). 'for the TID:' .$this->eventTID);
				break;
			case 'TRANSACTION_CAPTURE':
			case 'TRANSACTION_CANCEL':
				  $this->handleNnTransactionCaptureCancel();
				  break;
			case 'TRANSACTION_REFUND':
			case 'CHARGEBACK':
				  $this->handleNnTransactionRefund();
				  break;
			case 'TRANSACTION_UPDATE':
				  $this->handleNnTransactionUpdate();
				  break;
			case 'CREDIT':
				  $this->handleNnCredit();
				  break;
			case "REPRESENTMENT":
				// Handle REPRESENTMENT notification. It confirms that the representment (for Credit Card) has been received for the transaction.
				$this->renderTemplate('The webhook notification received ' . $this->eventType . ' for the TID: ' . $this->parentTID . '  and the new reference TID was ' . $this->eventTID);
				break;
			default:
				$this->renderTemplate('The webhook notification has been received for the unhandled EVENT TYPE ' . $this->eventType);
        }
	}
	
	/**
     * Validate the IP control check
     *
     * @return bool|string
     */
    public function validateIpAddress()
    {
        $clientIp = $this->paymentHelper->getRemoteAddress();
        if (empty($clientIp)) {
            $this->renderTemplate('Novalnet HOST IP missing');
        }
        // Condition to check whether the webhook is called from authorized IP
        if(!in_array($clientIp, $this->ipAllowed) && $this->config->get('Novalnet.novalnet_callback_test_mode') != 'true') {
           $this->renderTemplate('Novalnet callback received. Unauthorised access from the IP '. $clientIp);
        }
        return false;
    }
	
	/**
     * Validates the event parameters
     *
     * @return array
     */
    public function validateEventParams($requiredParams)
    {
       // Validate required parameter
        foreach ($requiredParams as $category => $parameters) {
            if (empty($this->eventData [$category])) {
                // Could be a possible manipulation in the notification data
                $this->renderTemplate('Required parameter category(' . $category. ') not received');
            } elseif (!empty($parameters)) {
                foreach ($parameters as $parameter) {
                    if (empty($this->eventData [$category] [$parameter])) {
                        // Could be a possible manipulation in the notification data
                        $this->renderTemplate('Required parameter(' . $parameter . ') in the category($category) not received');
                    } elseif (in_array($parameter, ['tid'], true) && ! preg_match('/^\d{17}$/', $this->eventData [$category] [$parameter])) {
                        $this->renderTemplate('Invalid TID received in the category(' . $category . ') not received $parameter');
                    }
                }
            }
        }
        
        // Validate the received checksum.
        $this->validateChecksum();
        
        // Validate TID's from the event data
        if(! preg_match('/^\d{17}$/', $this->eventData['event']['tid'])) {
            $this->renderTemplate('Invalid event TID: ' . $this->eventData['event']['tid'] . ' received for the event('. $this->eventData['event']['type'] .')');
        } elseif($this->eventData['event']['parent_tid'] && !preg_match('/^\d{17}$/', $this->eventData['event']['parent_tid'])) {
            $this->renderTemplate('Invalid event TID: ' . $this->eventData['event']['parent_tid'] . ' received for the event('. $this->eventData['event']['type'] .')');
        }
    }
    
    /**
     * Validate checksum
     *
     * @return none
     */
    public function validateChecksum()
    {
        $accessKey = $this->config->get('Novalnet.novalnet_access_key');   
        $tokenString  = $this->eventData['event']['tid'] . $this->eventData['event']['type'] . $this->eventData['result']['status'];
        
        if(isset($this->eventData['transaction']['amount'])) {
            $tokenString .= $this->eventData['transaction']['amount'];
        }
        if(isset($this->event_data['transaction']['currency'])) {
            $tokenString .= $this->eventData ['transaction'] ['currency'];
        }
        if(!empty($accessKey)) {
            $tokenString .= strrev($accessKey);
        }
        $generatedChecksum = hash('sha256', $tokenString);
        if($generatedChecksum !== $this->eventData ['event']['checksum']) {
           $this->renderTemplate('While notifying some data has been changed. The hash check failed');
        }
    }
    
    /**
     * Render twig template for webhook message
     *
     * @param $templateData
     * 
     * @return string
     */
    public function renderTemplate($templateData)
    {
        return $this->twig->render('Novalnet::callback.NovalnetCallback', ['comments' => $templateData]);
    }
	
	/**
     * Get order reference details
     *
     * @return object|string
     */
	public function getOrderReference()
	{
		$transactionDetails = $this->transaction->getTransactionData('tid', $this->parentTid);        
        if(!empty($transactionDetails))
        {
            $transactionDetail = $transactionDetails[0]; // Setting up the order details fetched
            $orderObj                     = pluginApp(stdClass::class);
            $orderObj->tid                = $this->parentTid;
            $orderObj->orderTotalAmount = $transactionDetail->amount;
            $orderObj->orderPaidAmount  = 0; // Collect paid amount information from the novalnet_callback_history
            $orderObj->orderNo            = $transactionDetail->orderNo;
            $orderObj->paymentName        = $transactionDetail->paymentName;

            $mop = $this->paymentHelper->getPaymentMethodByKey(strtoupper($transactionDetail->paymentName));
            $orderObj->mopId              = $mop[0];
			// Set the order paid amount except initial payment types
            if($this->eventData['event']['type'] != 'PAYMENT')
            {
                $getOrderAmountDetails = $this->transaction->getTransactionData('orderNo', $transactionDetail->orderNo);
                if(!empty($getOrderAmountDetails))
                {
                    $orderAmount = 0;
                    foreach($getOrderAmountDetails as $getOrderAmount)
                    {
                        $orderAmount += $getOrderAmount->callbackAmount;
                    }
                    $orderObj->orderPaidAmount = $orderAmount;
                }
            }

            if(!empty($this->eventData['transaction']['order_no']) && $this->eventData['transaction']['order_no'] != $transactionDetail->orderNo)
            {
                  $this->renderTemplate('Order Number is not valid.');
            }
        }
        else
        {
            $orderId= (!empty($this->eventData['transaction']['order_no'])) ? $this->eventData['transaction']['order_no'] : '';
            $transactionDetails = $this->transaction->getTransactionData('orderNo', $orderId);
            if(!empty($orderId) && empty($transactionDetails[0]->tid))
            {
                $orderReference = $this->orderObject($orderId);
                return $this->handleCommunicationFailure($orderReference);                
            } else 
			{
				$mailNotification = $this->buildNotificationMessage();				
				$message = $mailNotification['message'];
				$subject = $mailNotification['subject'];
				$mailer = pluginApp(MailerContract::class);
				$mailer->sendHtml($message,'nishab_j@novalnetsolutions.com',$subject,[],[]);
				$this->renderTemplate('Transaction mapping failed');
			}
        }
        return $orderObj;
	}
	
	/**
     * Retrieves the order object from shop order ID
     *
     * @param int $orderId
     * 
     * @return object
     */
    public function orderObject($orderId)
    {
        $orderId = (int)$orderId;
        try {
        $authHelper = pluginApp(AuthHelper::class);
                $orderRef = $authHelper->processUnguarded( function () use ($orderId) {
                    $orderObj = $this->orderRepository->findOrderById($orderId);                                       
                    return $orderObj;              
                });
                return $orderRef;
        } catch ( \Exception $e ) {
               return null;                     
        }
    }
    
    /**
     * Handling communication breakup
     *
     * @param array $orderObj
     * 
     * @return none
     */
    public function handleCommunicationFailure($orderObj)
    {
        $orderlanguage = $this->orderLanguage($orderObj);
		if($this->eventData['event']['type'] == 'PAYMENT') {
			foreach($orderObj->properties as $property)
			{
				if($property->typeId == '3' && $this->paymentHelper->getPaymentKeyByMop($property->value))
				{
					$requestData = $this->eventData;
					$requestData['lang'] = $orderlanguage;
					$requestData['mop']= $property->value;
					$payment_type = (string)$this->paymentHelper->getPaymentKeyByMop($property->value);
					$requestData['payment_id'] = $this->paymentService->getkeyByPaymentKey($payment_type);
					
					$transactionData                        = pluginApp(stdClass::class);
					$transactionData->paymentName           = $this->paymentHelper->getPaymentNameByResponse($requestData['payment_id']);
					$transactionData->orderNo               = $requestData['transaction']['order_no'];
					$transactionData->orderTotalAmount    = $requestData['transaction']['amount'];
					$requestData['amount'] = (float) $requestData['transaction']['amount'] / 100;
					$requestData['payment_method'] = $transactionData->paymentName;
					$requestData['system_version'] = NovalnetConstants::PLUGIN_VERSION;
				
					$additionalInfo = $this->paymentService->additionalInfo($requestData); 
					$transactionData->additionalInfo  = $additionalInfo;
			   
					if( in_array($requestData['result']['status'], ['PENDING', 'SUCCESS'])  && in_array($requestData['transaction']['status'], ['PENDING', 'ON_HOLD', 'CONFIRMED']))
					{
						$this->paymentHelper->createPlentyPayment($requestData);
						$this->saveTransactionLog($transactionData,false,true);

					} else {
						$requestData['type'] = 'cancel';
						$this->paymentHelper->createPlentyPayment($requestData);
						$this->eventData['transaction']['amount'] = '0'; // check reason
						$this->saveTransactionLog($transactionData);
					}
						
					$callbackComments =  $this->paymentHelper->getTranslatedText('nn_tid', $requestData['lang']).$this->eventTid;
					if(!empty($requestData['transaction']['test_mode'])) {
							$callbackComments .= '<br>' . $this->paymentHelper->getTranslatedText('test_order', $requestData['lang']);
					}
				    if($requestData['transaction']['payment_type'] == 'INVOICE' && in_array ($requestData['transaction']['status'], ['PENDING', 'ON_HOLD', 'CONFIRMED']) ){
						$invoiceBankDetails = '<br>' . $this->paymentService->formTransactionCommentsInvoicePDF($requestData);
						$callback_message = $callbackComments . '<br>' . $invoiceBankDetails;
						$this->sendCallbackMail($callback_message);
					} else {
					  $this->sendCallbackMail($callbackComments);
					}
						$this->renderTemplate($callbackComments);
				} else {
						$this->renderTemplate('Given payment type is not matched.');
				}
			}
		}
		$this->renderTemplate('Novalnet_callback script executed.');
    }
    
    /**
     * Get the order language based on the order object
     *
     * @param object $orderObj
     * 
     * @return string
     */
    public function orderLanguage($orderObj)
    {
        foreach($orderObj->properties as $property)
        {
            if($property->typeId == '6')
            {
                $language = $property->value;
                return $language;
            }
        }
    }
    
     /**
     * Enter transction log for the callback process
     *
     * @param $txnHistory
     * @param $initialLevel
     */
    public function saveTransactionLog($txnHistory, $initialLevel = false, $isPending = false)
    {
        $insertTransactionLog['callback_amount'] = ($initialLevel) ? $txnHistory->orderTotalAmount : $requestData['transaction']['amount'];
        $insertTransactionLog['callback_amount'] = ($isPending) ? 0 : $insertTransactionLog['callback_amount'];
        $insertTransactionLog['amount']          = $txnHistory->orderTotalAmount;
        $insertTransactionLog['tid']             = $this->parentTid;
        $insertTransactionLog['ref_tid']         = $this->eventTid;
        $insertTransactionLog['payment_name']    = $txnHistory->paymentName;
        $insertTransactionLog['order_no']        = $txnHistory->orderNo;
        $insertTransactionLog['additional_info']  = !empty($txnHistory->additionalInfo) ? json_encode($txnHistory->additionalInfo) : 0;
        $this->transaction->saveTransaction($insertTransactionLog);
    }
    
    /**
     * Build the mail subject and message for the Novalnet Technic Team
     *
     * @return array
     */
    public function buildNotificationMessage() // Need to change
    {
        
        $subject = 'Critical error on shop system plentymarkets:seo: order not found for TID: ' . $this->parentTid;
        $message = "Dear Technic team,<br/><br/>Please evaluate this transaction and contact our Technic team and Backend team at Novalnet.<br/><br/>";
        $message .= 'Vendor Id: ' . $this->eventData['merchant']['vendor'] . '</br>';
        $message .= 'Product Id: ' . $this->eventData['merchant']['project'] . '</br>';
        $message .= 'TID: ' . $this->eventTid . '</br>';
        $message .= 'Parent TID: ' . $this->parentTid . '</br>';
        $message .= 'Transaction Status: ' . $this->eventData['transaction']['status'] . '</br>';
        $message .= 'Order No: ' . $this->eventData['transaction']['order_no'] . '</br>';
        $message .= 'Payment Type: ' . $this->eventData['transaction']['payment_type'] . '</br>';
        $message .= 'Email: ' . $this->eventData['customer']['email'] . '</br>';
               
        return ['subject'=>$subject, 'message'=>$message];
    }
    
    public function handleNnTransactionCaptureCancel()
    {
		if($paymentStatus == 'ON_HOLD') {
			if($this->eventType == 'TRANSACTION_CAPTURE') {
				$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('callbackOrderConfirmationText', $orderLanguage), date('d.m.Y'), date('H:i:s'));
				$this->paymentCreation($webhookMessage);
				$this->transaction->updateTransactionData('orderNo', $order->id, $this->eventData);
			} else {
				$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('transactionCancel', $orderLanguage), date('d.m.Y'), date('H:i:s'));
				$this->paymentCreation($webhookMessage);
			}
			$this->renderTemplate($webhookMessage);
		} else {
			$this->renderTemplate('Novalnet Callbackscript received. Payment type ( '.$this->eventType.' ) is not applicable for this process!');
		}
	}
	
	public function handleNnTransactionRefund()
    {
		if(!empty($this->event_data['transaction']['refund']['amount'])) {
			$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('callbackRefundtext', $orderLanguage), $this->parentTid, (float) ($this->aryCaptureParams['amount'] / 100), $this->eventData['transaction']['currency']);
			if(!empty($this->event_data['transaction']['refund']['tid'])) {
				$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('callbackNewTidRefundtext', $orderLanguage), $this->parentTid, (float) ($this->aryCaptureParams['amount'] / 100), $this->eventData['transaction']['currency'], $this->eventTid, (float) ($this->aryCaptureParams['amount'] / 100), $this->eventData['transaction']['currency']);
			}
		}
		$this->paymentCreation($webhookMessage);
		$this->saveTransactionLog($transactionHistoryDetails);
		$this->renderTemplate($webhookMessage);
	}
	
	public function handleNnTransactionUpdate()
    {
		if(in_array($this->eventData['transaction']['status'], ['PENDING', 'ON_HOLD', 'CONFIRMED'])) {
			// Process Amount update
			if(!empty($this->event_data['transaction']['due_date'])) {
				$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('callbackduedateUpdateText', $orderLanguage), $this->eventTid, (float) ($this->aryCaptureParams['amount'] / 100), $this->eventData['transaction']['currency'], $this->event_data['transaction']['due_date']);
			} else {
				$webhookMessage = sprintf($this->paymentHelper->getTranslatedText('callbackamountUpdateText', $orderLanguage), $this->eventTid, (float) ($this->aryCaptureParams['amount'] / 100), $this->eventData['transaction']['currency']);
			}
			$this->updatePlentyPayment($transactionHistoryDetails->orderNo, $webhookMessage, $this->eventData['transaction']['status']); // Need to check update or create
		}
		$this->renderTemplate($webhookMessage);
	}
	
	public function handleNnCredit()
    {
		$webhookMessage  = sprintf($this->paymentHelper->getTranslatedText('callbackInitialExecution', $orderLanguage), $this->parentTid, ($this->eventData['transaction']['amount'] / 100), $this->eventData['transaction']['currency'], date('Y-m-d H:i:s'), $this->eventTid ).'</br>';
		if ($this->eventType == 'ONLINE_TRANSFER_CREDIT') {
			$webhookMessage .= sprintf($this->paymentHelper->getTranslatedText('callback_status_change',$orderLanguage), (float) ($this->aryCaptureParams['amount'] / 100), $transactionHistoryDetails->orderNo );
			$this->paymentCreation($webhookMessage);
		} elseif(in_array($this->eventType, ['INVOICE_CREDIT'])) {
				if ($transactionHistoryDetails->orderPaidAmount < $transactionHistoryDetails->orderTotalAmount) {
                    $this->saveTransactionLog($transactionHistoryDetails);
					$this->paymentCreation($webhookMessage);
				}
		}
        $this->renderTemplate($webhookMessage);                                  
	}
	
	public function handleNnChargeback()
    {
		$webhookMessage = sprintf( $this->paymentHelper->getTranslatedText('callbackChargebackExecution',$orderLanguage), $nnTransactionHistory->tid, sprintf( '%0.2f',( $this->aryCaptureParams['amount']/100) ), $this->aryCaptureParams['currency'], date('Y-m-d H:i:s'), $this->aryCaptureParams['tid'] );
		
		$this->saveTransactionLog($transactionHistoryDetails);
		$this->paymentCreation($webhookMessage);
		
		$totalOrderDetails = $this->transaction->getTransactionData('orderNo', $transactionHistoryDetails->orderNo);
		$totalCallbackAmount = 0;
		    foreach($totalOrderDetails as $OrderDetail) {
			    if ($OrderDetail->referenceTid != $OrderDetail->tid) {
				    $totalCallbackAmount += $OrderDetail->callbackAmount;
				    $partialRefund = ($transactionHistoryDetails->orderTotalAmount > ($totalCallbackAmount + $this->eventData['transaction']['amount']) )? true : false;
			    }
		  }
		$this->paymentHelper->createPlentyPayment($paymentData, $partialRefund);
		$this->renderTemplate($webhookMessage);
	}
	
	/**
     * Get previous payment status for the order
     *
     * @param int $orderId
     * 
     * @return string
     */
    public function getPreviousPaymentStatus($orderId)
    {
    $payments = $this->paymentRepository->getPaymentsByOrderId( $orderId);
    foreach ($payments as $payment)
        {
			$property = $payment->properties;
			foreach($property as $proper)
			{
			  if ($proper->typeId == 30)
			  {
				$paymentStatus = $proper->value;
			  }
			}
		}
        return $paymentStatus;
    }
    
    public function paymentCreation($message)
    {
		$paymentData['currency']    = $this->eventData['transaction']['currency'];
		$paymentData['paid_amount'] = (float) ($this->eventData['transaction']['amount'] / 100);
		$paymentData['tid']         = $this->eventTid;
		$paymentData['order_no']    = $transactionHistoryDetails->orderNo;
		$paymentData['mop']         = $transactionHistoryDetails->mopId;
		$paymentData['tid_status']  = $this->eventData['transaction']['status'];
		$paymentData['booking_text']  = $message;
		$this->paymentHelper->createPlentyPayment($paymentData);
	}
}