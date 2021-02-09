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

$options['headers'] = [
    'Content-Type' => 'application/json',
    'Charset' => 'utf-8',
    'Accept' => 'application/json',
    'X-NN-Access-Key' => base64_encode(SdkRestApi::getParam('nn_access_key'))
];
$client = new \GuzzleHttp\Client($options);
try {
    $response = $client->post(SdkRestApi::getParam('nn_request_process_url'), ['body' => json_encode(SdkRestApi::getParam('nn_request'))]);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
}
return json_decode((string)$response->getBody(), true);




