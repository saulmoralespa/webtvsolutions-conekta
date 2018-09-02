<?php
// Sample external payment processor script

// Config vars
$key = "key_UaxdBaQtkqje6e6pAnmipEA"; // this key must match the key entered in the external payment processor configuration of the Store extension (Store > Configuration). Keep it SAFE!.
$webtv_base_url = "https://cofide.net"; // use https if you have enabled it in the WebTV; otherwise, use http

// ==================================== The data sent by the WebTV... ====================================
// When the user clicks the "Pay Order" button, in the WebTV, it will be redirected to the payment procesor URL (this script...).
// For doing that, you must supply the corresponding URL in the external payment processor configuration of the Store extension (Store > Configuration)
// The order data will be appended to the payment processor URL as get vars
// Expect the following GET vars:
//  id_gateway     -> ID of the WebTV payment gateway
//  id_order       -> ID of the order to pay
//  amount         -> Amount to pay (decimal, for example: 10.5, 5, ...)
//  currency_code  -> Currency of the order (USD, EUR, etc.)
//  signature      -> A signature for validating the request
if (empty($_GET)) die("no vars???...");
$id_gateway    = (isset($_GET["id_gateway"]))? $_GET["id_gateway"] : -1;
$id_order      = (isset($_GET["id_order"]))? $_GET["id_order"] : -1;
$amount        = (isset($_GET["amount"]))? $_GET["amount"] : 0;
$currency_code = (isset($_GET["currency_code"]))? $_GET["currency_code"] : "";
$order_number  = (isset($_GET["order_number"]))? $_GET["order_number"] : "";
$signature     = (isset($_GET["signature"]))? rawurldecode($_GET["signature"]) : ""; // you should not need to use urldecode here, but if you are not gettting the signature correctly then use urldecode()

// Verify that you have received all the data
if ( $id_gateway==-1)    die("invalid gateway ID...");
if ( $id_order==-1)      die("invalid order ID...");
if ( $amount==0)         die("invalid order amount...");
if ( $currency_code=="") die("invalid currency code...");
if ( $order_number=="")  die("invalid order number...");
if ( $signature=="")     die("invalid signature...");

// Now, generate a signature to compare against the one in the "signature" variable.
$data = array(
    "id_gateway"    => (string)$id_gateway,
    "id_order"      => (string)$id_order,
    "amount"        => (string)$amount,
    "currency_code" => $currency_code,
    "order_number"  => (string)$order_number
);
$computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key, true));

// If the generated signature is different than the received one then don't continue!
if ( $computed_signature!=$signature) die("hmmm, invalid signature");
// The signature guarantees that the data has not been altered.
// If the signatures don't match, and you are sure the data has not been altered,
// then check if the key supplied to the WebTV is the same key you are using in this script


// ==================================== Process payment ====================================
// After verifying everything is OK, then process the payment...
// Please, note that the $id_gateway and $id_order will be required at a later time in order to return to the WebTV and process the order.

// ...
// ...
// ...

$status="SUCCESS";       // Use "ERROR" if something went wrong...
$status_msg="";          // (If ERROR...) Provide an error message: The WebTV will display it to the User.
$transactionID="123456"; // (If SUCCESS...). The transaction ID will be sent to the WebTV and stored into the order log.

//Error example
/*
	$status="ERROR";
	$status_msg="Error processing credit card";
	$transactionID="";
*/

// ...
// ...
// ...



// ==================================== Returning to the WebTV ====================================
// Once the payment is complete, you need to return to the WebTV so it can set the order as "paid" and process it.

// Preparing the data for validation
$data = array(
    "id_gateway"     => (string)$id_gateway,
    "id_order"       => (string)$id_order,
    "status"         => $status,
    "id_transaction" => (string)$transactionID
);
// Now, generate a signature for the data
$computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key, true));

// Building the return URL
if ( substr( $webtv_base_url, -1, 1 ) != '/' ) $return_url = $webtv_base_url.'/';
else $return_url = $webtv_base_url;
$return_url .=
    'payment.php'. // comment this line if using friendly URLS with folder style
    '?go=store'.
    '&do=payOrder'.
    '&iq='.$id_order.
    '&tp=gid_'.$id_gateway.'-step_2'.
    '&status='.$status.
    '&status_msg='.urlencode($status_msg).
    '&transaction='.urlencode($transactionID).
    '&signature='.urlencode($computed_signature);

// Redurect tthe User to the WebTV
header('Location: '.$return_url);
exit;


?>