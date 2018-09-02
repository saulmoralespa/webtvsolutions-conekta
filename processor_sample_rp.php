<?php
// Sample external payment processor script

// Config vars
$key = "the_shared_secret_key"; // this key must match the key entered in the external payment processor configuration of the Store extension (Store > Configuration). Keep it SAFE!.
$webtv_base_url = "https://the_webtv_base_url"; // use https if you have enabled it in the WebTV; otherwise, use http

if (empty($_GET)) die("no vars???...");

/* ++++++++++++++++++++++++++++++++++++++++++++++ */
/* ++++++++++ CASE1: Payment requests ++++++++++ */
/* ++++++++++++++++++++++++++++++++++++++++++++++ */
//
// For this case the "action" URL var is optional; however, if present it must have a value of "pay"
//
if ( !isset($_GET["action"]) || ( isset($_GET["action"]) && $_GET["action"]=="pay" ) )
{
    // ==================================== Receive the data sent by the WebTV... ====================================
    // When the user clicks the "Pay Order" button, in the WebTV, it will be redirected to the payment procesor URL (this script...).
    // For doing that, you must supply the corresponding URL in the external payment processor configuration of the Store extension (Store > Configuration)
    // The order data will be appended to the payment processor URL as get vars

    // One-time payments + recurring payments
    // The WebTV will send the one-time payment as well as the recurring payment info in the same URL

    // ------------------------- One time payment info -------------------------
    // Expect the following GET vars:
    //  id_gateway     -> ID of the WebTV payment gateway
    //  id_order       -> ID of the order to pay
    //  amount         -> Amount to pay (decimal, for example: 10.5, 5, ...)
    //  currency_code  -> Currency of the order (USD, EUR, etc.)
    //  signature      -> A signature for validating the request

    // Collect the data
    $id_gateway    = (isset($_GET["id_gateway"]))?    $_GET["id_gateway"] : -1;
    $id_order      = (isset($_GET["id_order"]))?      $_GET["id_order"] : -1;
    $amount        = (isset($_GET["amount"]))?        $_GET["amount"] : 0;
    $currency_code = (isset($_GET["currency_code"]))? $_GET["currency_code"] : "";
    $order_number  = (isset($_GET["order_number"]))?  $_GET["order_number"] : "";
    $signature     = (isset($_GET["signature"]))?     rawurldecode($_GET["signature"]) : ""; // you should not need to use urldecode here, but if you are not gettting the signature correctly then use urldecode()

    // Verify that you have received all the data
    if ( $id_gateway==-1)    die("invalid gateway ID...");
    if ( $id_order==-1)      die("invalid order ID...");
    if ( $amount==0)
    {
        if ( ! (isset($_GET["rp_num"]) && $_GET["rp_num"]>0) )  // NEW (v3.0.2): Consider trials! allow amount 0 if there are recurrig payments
            die("invalid order amount...");
    }
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


    // ------------------------- Recurring payments -------------------------
    // In case the WebTV requests the setup of recurring payments then it will include additional URL vars
    // Expect the following GET vars:
    //  rp_num                  -> Number of recurring payment requests
    //                             Each recurring payment data fields are prefixed rp_n_{data_field_name}, where n is the index/number of the request.
    //                             Please note that the index/number is required when returning to the WebTV
    //  rp_n_sku                -> SKU of the recurring payment request n - You can use this as a description
    //  rp_n_amount             -> Amount of the recurring payment request n (currency is the same as the one time payment request)
    //  rp_n_period             -> Period of the recurring payment request n (can be DAY, WEEK, MONTH or YEAR)
    //  rp_n_period_frequency   -> Frequency for the period of the recurring payment request n (int value)
    //                             period and period_frequecy determine the time interval for you to charge the buyer's credit card
    //  rp_n_first_payment_date -> Date of the first payment (Unix timestamp with UTC time zone). The is the date when you have to charge the buyer's credit card for the first time
    //                             For example, if period is MONTH, period_frequency is 1 and first_payment_date is 22 Feb 2016, then
    //                                payment 1 = 22 Feb 2016
    //                                payment 2 = 22 Mar 2016
    //                                payment 3 = 22 Apr 2016
    //  rp_n_signature          -> A signature for validating the recurring payment request n

    // Collect the data
    $recurring_payments = array();
    if ( isset($_GET["rp_num"]) && $_GET["rp_num"]>0)
    {
        for ( $i=0; $i<$_GET["rp_num"]; $i++)
        {
            // Collect the recurring payment data "i"
            $rp_sku                = (isset($_GET["rp_".$i."_sku"]))?                rawurldecode($_GET["rp_".$i."_sku"]) : "";
            $rp_amount             = (isset($_GET["rp_".$i."_amount"]))?             $_GET["rp_".$i."_amount"] : 0.0;
            $rp_period             = (isset($_GET["rp_".$i."_period"]))?             $_GET["rp_".$i."_period"] : "";
            $rp_period_frequency   = (isset($_GET["rp_".$i."_period_frequency"]))?   $_GET["rp_".$i."_period_frequency"] : 0;
            $rp_first_payment_date = (isset($_GET["rp_".$i."_first_payment_date"]))? $_GET["rp_".$i."_first_payment_date"] : 0;
            $rp_signature          = (isset($_GET["rp_".$i."_signature"]))?          rawurldecode($_GET["rp_".$i."_signature"]) : "";

            // Now, generate a signature to compare against the one in the "signature" variable.
            $rp_computed_signature = base64_encode(hash_hmac('sha256', md5( $rp_sku . floatval($rp_amount) . $rp_period_frequency . $rp_period  )  , $key, true));

            // If the generated signature is different than the received one then don't  process this item but mark it as failed!
            // The signature guarantees that the data has not been altered.
            // If the signatures don't match, and you are sure the data has not been altered,
            // then check if the key supplied to the WebTV is the same key you are using in this script
            if ( $rp_computed_signature!=$rp_signature)
            {
                // Add a failed item (skip this from your processing but keep it as failed in order to return the result to the WebTV)
                // Failed items must have an array index "error", otherwise that array index must not be included in the return
                $recurring_payments[$i]=array(
                    "error"              => "Invalid recurring payment data or signature mismatch",
                    "sku"                => "",
                    "amount"             => 0,
                    "period"             => "",
                    "period_frequency"   => 0,
                    "first_payment_date" => 0
                );
            }
            else
            {
                // register
                $recurring_payments[$i]=array(
                    "sku"                => $rp_sku,
                    "amount"             => $rp_amount,
                    "period"             => $rp_period,
                    "period_frequency"   => $rp_period_frequency,
                    "first_payment_date" => $rp_first_payment_date
                );
            }
        }
    }

    //print_r($_GET); exit;

    // ==================================== Process payments ====================================
    // After verifying everything is OK, then process the payment...
    // Please, note that the $id_gateway and $id_order will be required at a later time in order to return to the WebTV and process the order.

    // ------------------------- One time payment -------------------------

    if ( $amount== 0 ) // NEW (v3.0.2): Consider trial case
    {
        // Prepare the data for the return
        $status="SUCCESS";
        $status_msg="";
        $transactionID="N/A";
    }
    else // normal case
    {
        // Do your stuff here to process the one-time payment

        // ...
        // ...
        // ...

        // Prepare the data for the return
        $status="SUCCESS";       // Use "ERROR" if something went wrong...
        $status_msg="";          // (If ERROR...) Provide an error message: The WebTV will display it to the User.
        $transactionID="123456"; // (If SUCCESS...). The transaction ID will be sent to the WebTV and stored into the order log.
    }

    //Error example
    /*
        $status="ERROR";
        $status_msg="Error processing credit card";
        $transactionID="";
    */

    // ------------------------- Recurring payments -------------------------
    // For each recurring payment, the WebTV needs a "Profile ID".
    // Your payment processor must keep track of each recurring payment in order to bill it when appropriate. Normally, you would save this info on a database
    // and process the payments with this or with another script. In any case, you must always know the "status" of a profile as well as the date of the last (or next) payment.
    // The "Profile ID" is your internal ID for the recurring payment. It will be used by the WebTV to ask you the status of the profile or to request its cancellation

    if ( !empty($recurring_payments))
    {
        // Remember, recurring payments array, if not empty, will have the following indexes:
        //    error - only if it is an item which could not ve validaded
        //    sku
        //    amount
        //    period
        //    period_frequency
        //    first_payment_date
        // After processing, it must have two additional indexes
        //    profile_id	// empty "" if error
        //    status		// The status can be: Active, Pending, Cancelled, Suspended, Expired

        foreach ( $recurring_payments as $rp_index => $rp_data)
        {
            if ( !isset($rp_data["error"]) )
            {
                // Valid item: Do your stuff here to process this recurring payment request (save to the database, etc...)

                // ...
                // ...
                // ...

                // If success
                // Prepare the data for the return
                $recurring_payments[$rp_index]["profile_id"]="MyUniqueInternalProfileID";   // The aforementioned Profile ID. Again, this will be used by the WebTV to ask this script info about the profile status or to cancel it.
                $recurring_payments[$rp_index]["status"]="Active";                          // The status can be: Active (this must be the case if success), Pending, Cancelled, Suspended, Expired

                // NEW (v3.0.2): If error...
                /*
                $recurring_payments[$rp_index]["profile_id"]=""; // on error, profile_id is empty ("")
                $recurring_payments[$rp_index]["error"]="Error creating recurring payment profile"; // return the details of the error here
                $recurring_payments[$rp_index]["status"]="Pending";  // on error, status = "Pending"
                */

            }
            else
            {
                // Failed item: Skip from your processing

                // Prepare the data for the return
                $recurring_payments[$rp_index]["profile_id"]="";
                $recurring_payments[$rp_index]["status"]="Invalid profile";
            }
        }
    }


    // ==================================== Returning to the WebTV ====================================

    // ------------------------- One time payment -------------------------
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
        'index.php'. // comment this line if using friendly URLS with folder style
        '?go=store'.
        '&do=payOrder'.
        '&iq='.$id_order.
        '&tp=gid_'.$id_gateway.'-step_2'.
        '&status='.$status.
        '&status_msg='.urlencode($status_msg).
        '&transaction='.urlencode($transactionID).
        '&signature='.urlencode($computed_signature);


    // ------------------------- Recurring payments data -------------------------
    // You also need to return the corresponding data for the recurring payments (if any)
    if ( !empty($recurring_payments))
    {
        // set recurring payments return flag
        $return_url = str_replace('-step_2&','-step_2-rp_1&',$return_url);

        foreach ( $recurring_payments as $rp_index => $rp_data)
        {
            // Preparing the data for validation
            $rp_signature = base64_encode(hash_hmac('sha256', md5( $rp_data["profile_id"] . $rp_data["status"]  )  , $key, true));

            // Append URL vars to the return URL
            if ( isset($rp_data["error"])) $return_url .= "&rp_".$rp_index."_error=".urlencode($rp_data["error"]);
            $return_url .= "&rp_".$rp_index."_profile_id=".urlencode($rp_data["profile_id"]);
            $return_url .= "&rp_".$rp_index."_status=".urlencode($rp_data["status"]);
            $return_url .= "&rp_".$rp_index."_first_payment_date=".$rp_data["first_payment_date"];
            $return_url .= "&rp_".$rp_index."_signature=".urlencode($rp_signature);
        }
    }

    // Redirect the User to the WebTV
    header('Location: '.$return_url);


    // xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    //echo "redirect URL:".$return_url; exit;
    // TEMPORAL redirect using HTML...
    //echo '<META http-equiv="refresh" content="2;URL='.$return_url.'">'.PHP_EOL;
    //echo 'Redirection URL: '.$return_url.'<br>'.PHP_EOL;
    //echo 'Status: '.$status.'<br>'.PHP_EOL;
    //echo 'id_gateway: '.$id_gateway.'<br>'.PHP_EOL;
    //echo 'id_transaction: '.$transactionID.'<br>'.PHP_EOL;
    //echo 'id_order: '.$id_order.'<br>'.PHP_EOL;
    //echo 'signature: '.$computed_signature.'<br>'.PHP_EOL;

}

/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* ++++++++++ CASE2: Recurring payments status check requests ++++++++++ */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
//
// For this case the "action" URL var must have a value of "rp_status"
//
else if ( isset($_GET["action"]) && $_GET["action"]=="rp_status")
{
    // Recurring payments status check
    // Please note that the processor response must always be a JSON encoded array

    // ==================================== The data sent by the WebTV... ====================================
    // When the WebTV needs to check the status of a recurring payment profile, it will do so by calling this processor and passing the GET var "action" with value "rp_status"
    // Expect the following GET vars:
    //  profile_id     -> ID of the recurring payment profile (this was the ID this processor returned to the WebTV when setting up a recurring payment profile)
    //  signature      -> A signature for validating the request
    $profile_id    = (isset($_GET["profile_id"]))? $_GET["profile_id"] : "";
    $signature     = (isset($_GET["signature"]))? rawurldecode($_GET["signature"]) : ""; // you should not need to use urldecode here, but if you are not gettting the signature correctly then use urldecode()

    // Verify that you have received all the data
    if ( $profile_id=="" ) { echo json_encode( array("error"=>"invalid profile ID..." ) ); exit; }
    if ( $signature=="" )  { echo json_encode( array("error"=>"invalid signature..." )  ); exit; }

    // Now, generate a signature to compare against the one in the "signature" variable.
    $data = array(
        "action"        => "rp_status",
        "profile_id"    => (string)$profile_id
    );
    $computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key, true));

    // If the generated signature is different than the received one then don't continue!
    if ( $computed_signature!=$signature) { echo json_encode( array("error"=>"hmmm, invalid signature" ) ); exit; }
    // The signature guarantees that the data has not been altered.
    // If the signatures don't match, and you are sure the data has not been altered,
    // then check if the key supplied to the WebTV is the same key you are using in this script

    // ==================================== Process ====================================
    // After verifying everything is OK, then do your stuff here...

    // ...
    // ...
    // This would normally imply querying your database to check the status of a recurring payment profile

    $status = "Active";              // posible values: Active, Pending, Cancelled, Suspended, Expired
    $last_payment_date = 1421085297; // last (successful ) payment date (Unix timestamp, with UTC time zone) -> IMPORTANT: This will be used by the WebTV if a subscription is actually paid
    $next_payment_date = 1421085297; // next payment date (Unix timestamp, with UTC time zone)

    // ...
    // ...

    // ==================================== Response ====================================
    // Once the payment is complete, you need to return the recurring payment profile status data to the WebTV.
    //
    echo json_encode( array(
        "status"            => $status,             // Remember, on of the following: Active, Pending, Cancelled, Suspended, Expired
        "last_payment_date" => $last_payment_date,
        "next_payment_date" => $next_payment_date
    ));

}

/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* ++++++++++ CASE3: Recurring payments cancellation requests ++++++++++ */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
//
// For this case the "action" URL var must have a value of "rp_cancel"
//
else if ( isset($_GET["action"]) && $_GET["action"]=="rp_cancel")
{
    // Recurring payments cancellation
    // Please note that the processor response must always be a JSON encoded array

    // ==================================== The data sent by the WebTV... ====================================
    // When the WebTV sends a "Cancel" request fo a recurring payment profile, it will do so by calling this processor and passing the GET var "action" with value "rp_cancel"
    // Expect the following GET vars:
    //  profile_id     -> ID of the recurring payment profile (this was the ID this processor returned to the WebTV when setting up a recurring payment profile)
    //  signature      -> A signature for validating the request
    $profile_id    = (isset($_GET["profile_id"]))? $_GET["profile_id"] : "";
    $signature     = (isset($_GET["signature"]))? rawurldecode($_GET["signature"]) : ""; // you should not need to use urldecode here, but if you are not gettting the signature correctly then use urldecode()

    // Verify that you have received all the data
    if ( $profile_id=="" ) { echo json_encode( array("error"=>"invalid profile ID..." ) ); exit; }
    if ( $signature=="" )  { echo json_encode( array("error"=>"invalid signature..." )  ); exit; }

    // Now, generate a signature to compare against the one in the "signature" variable.
    $data = array(
        "action"        => "rp_cancel",
        "profile_id"    => (string)$profile_id
    );
    $computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key, true));

    // If the generated signature is different than the received one then don't continue!
    if ( $computed_signature!=$signature) { echo json_encode( array("error"=>"hmmm, invalid signature" ) ); exit; }
    // The signature guarantees that the data has not been altered.
    // If the signatures don't match, and you are sure the data has not been altered,
    // then check if the key supplied to the WebTV is the same key you are using in this script

    // ==================================== Process ====================================
    // After verifying everything is OK, then do your stuff here...

    // ...
    // ...

    $status = "Cancelled";            // if you want to let know the WebTV that the recurtring payment profile was cancelled, then the returned status must be "Cancelled".
    // NOTE: In case of an error just return a JSON encoded array with the message.
    //       Example: echo json_encode( array("error"=>"internal error, could not cancel recurring payment profile..." )  ); exit;

    // ...
    // ...

    // ==================================== Response ====================================
    // Once the payment is complete, you need to return the recurring payment profile "Cancel" status to the WebTV.
    //
    echo json_encode( array(
        "status"            => $status
    ));

}

?>