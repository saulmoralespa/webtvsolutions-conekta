<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 30/08/18
 * Time: 05:28 PM
 */

include_once ('config.php');
require_once ('conekta-php/lib/Conekta.php');

\Conekta\Conekta::setApiKey($keyPrivate);
\Conekta\Conekta::setApiVersion("2.0.0");
\Conekta\Conekta::setLocale('es');


if ($_POST['payment'] == 'cash'){
    $id_order = $_POST['id_order'];
    $currency_code = $_POST['currency_code'];
    $amount = Amount($_POST['amount']);
    $order_number = $_POST['order_number'];
    $name_cash = $_POST['name_cash'];
    $email_cash = $_POST['email_cash'];
    $phone_cash = $_POST['phone_cash'];
    $payment_method = $_POST['payment_method'];


    $order =
        array(
            'line_items'=> array(
                array(
                    'name'        => $order_number,
                    'description' => $order_number,
                    'unit_price'  => $amount,
                    'quantity'    => 1
                )
            ),
            'currency'    => $currency_code,
            'metadata'    => array('order_number' => $order_number),
            'charges'     => array(
                array(
                    'payment_method' => array(
                        'type'       => $payment_method,
                        'expires_at' => strtotime(date("Y-m-d H:i:s")) + "36000"
                    ),
                    'amount' => $amount
                )
            ),
            'currency'      => $currency_code,
            'customer_info' => array(
                'name'  => $name_cash,
                'phone' => $phone_cash,
                'email' => $email_cash
            )
        );
}else{
    $id_order = $_POST['id_order'];
    $currency_code = $_POST['currency_code'];
    $amount = Amount($_POST['amount']);
    $order_number = $_POST['order_number'];




    try {

        $customer = \Conekta\Customer::create(
            array(
                "name" => $_POST['name'],
                "email" => $_POST['email'],
                "payment_sources" => array(
                    array(
                        "type" => "card",
                        "token_id" => $_POST['conektaTokenId']
                    )
                )
            )
        );

    } catch (\Conekta\ProcessingError $error){
        die($error->getMessage());
    } catch (\Conekta\ParameterValidationError $error){
        die($error->getMessage());
    } catch (\Conekta\Handler $error){
        die($error->getMessage());
    }


    if (isset($_POST['subscriptions'])){

        $recurring_payments = subscriptionRecurrent();

        $amount_subscripcion = Amount($recurring_payments[0]['amount']);

        try {
            $plan = \Conekta\Plan::find($recurring_payments[0]['sku']);
        } catch (\Conekta\ProcessingError $error){
            die($error->getMessage());
        } catch (\Conekta\ParameterValidationError $error){
            die($error->getMessage());
        } catch (\Conekta\Handler $error){
            createPlan($amount_subscripcion,$recurring_payments);
        }



        try {
            $subscription = $customer->createSubscription(
                array(
                    'plan' => $recurring_payments[0]['sku']
                )
            );
        } catch (\Conekta\ProcessingError $error){
            die($error->getMessage());
        } catch (\Conekta\ParameterValidationError $error){
            die($error->getMessage());
        } catch (\Conekta\Handler $error){
            die($error->getMessage());
        }


    }


    if(isset($customer->id) && !isset($_POST['payment']) || ($amount > 0 && isset($amount_subscripcion))){
        $order = array(
            "line_items" => array(
                array(
                    "name" => $order_number,
                    "unit_price" => $amount,
                    "quantity" => 1
                )
            ),
            "currency" =>  $currency_code,
            "customer_info" => array(
                "customer_id" => $customer->id
            ),
            "metadata" => array('order_number' => $order_number),
            "charges" => array(
                array(
                    "payment_method" => array(
                        "type" => "default"
                    )
                )
            )
        );
    }
}

if(isset($_POST['payment']) || (!isset($_POST['payment']) && $amount > 0 && !isset($amount_subscripcion))  ||  ($amount > 0 && isset($amount_subscripcion))){

    try {
        $order = \Conekta\Order::create($order);

        if (isset($_POST['payment']) && !isset($amount_subscripcion)){ //cash
            echo json_encode(array('status' => true, 'data' => $order));
        }elseif (!isset($_POST['payment']) || isset($amount_subscripcion)){ // payment with credit

            if ($order->payment_status == 'paid')
                $url = genereteUrlReturn($order->id);
            else
                $url = genereteUrlReturn($order->id, $order->payment_status, 'ERROR');

            if (isset($amount_subscripcion) && $amount > 0){
                $data_recurrents = addCustomerId($subscription->customer_id);
                $url = genereteUrlReturn($subscription->id);
                $url = validationEncodeData($data_recurrents, $url);
            }

            echo json_encode(array('status' => true, 'data' => array('url' => $url)));
        }

    } catch (\Conekta\ProcessingError $e){
        echo json_encode(array('status' => false, 'msj' => $e->getMessage()));
    } catch (\Conekta\ParameterValidationError $e){
        echo json_encode(array('status' => false, 'msj' => $e->getMessage()));
    }
}elseif (!isset($_POST['payment']) && $amount === 0 && isset($amount_subscripcion)){
    $data_recurrents = addCustomerId($subscription->customer_id);
    $url = genereteUrlReturn($subscription->id);
    $url = validationEncodeData($data_recurrents, $url);
    echo json_encode(array('status' => true, 'data' => array('url' => $url)));
}

function genereteUrlReturn($transactionID, $status_msg="", $status = "SUCCESS"){

    global $key_signature;
    global $webtv_base_url;

    $data = array(
        "id_gateway"     => (string)$_POST['id_gateway'],
        "id_order"       => (string)$_POST['id_order'],
        "status"         => $status,
        "id_transaction" => (string)$transactionID
    );

    $computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key_signature, true));

    // Building the return URL
    if ( substr( $webtv_base_url, -1, 1 ) != '/' ) $return_url = $webtv_base_url.'/';
    else $return_url = $webtv_base_url;
    $return_url .=
        //'payment.php'. // comment this line if using friendly URLS with folder style
        '?go=store'.
        '&do=payOrder'.
        '&iq='.$_POST['id_order'].
        '&tp=gid_'.$_POST['id_gateway'].'-step_2'.
        '&status='.$status.
        '&status_msg='.urlencode($status_msg).
        '&transaction='.urlencode($transactionID).
        '&signature='.urlencode($computed_signature);

    return $return_url;
}

function createPlan($amount, $recurring_payments, $currency_code = "MXN"){

    try {
        $plan = \Conekta\Plan::create(
            array(
                "id" => $recurring_payments[0]['sku'],
                "name" => $recurring_payments[0]['sku'],
                "amount" => $amount,
                "currency" => $currency_code,
                "interval" => "month",
                'frequency' => (int)$recurring_payments[0]['period_frequency'],
                'trial_period_days' => trialDays($recurring_payments[0]['first_payment_date']),
            )
        );
    } catch (\Conekta\ProcessingError $error){
        die($error->getMessage());
    } catch (\Conekta\ParameterValidationError $error){
        die($error->getMessage());
    } catch (\Conekta\Handler $error){
        die($error->getMessage());
    }
}

function subscriptionRecurrent(){
    return json_decode($_POST['subscriptions'], true);
}

function addCustomerId($customer_id, $status = 'Active'){


    $recurring_payments = subscriptionRecurrent();

    foreach ($recurring_payments as $rp_index  => $rp_data)
    {
        $recurring_payments[$rp_index]["profile_id"] = $customer_id;
        $recurring_payments[$rp_index]["status"] = $status;
    }

    return $recurring_payments;
}

function validationEncodeData($recurring_payments, $return_url){

    global $key_signature;

    $return_url = str_replace('-step_2&','-step_2-rp_1&',$return_url);

    foreach ( $recurring_payments as $rp_index => $rp_data)
    {
        // Preparing the data for validation
        $rp_signature = base64_encode(hash_hmac('sha256', md5( $rp_data["profile_id"] . $rp_data["status"]  )  , $key_signature, true));

        // Append URL vars to the return URL
        if ( isset($rp_data["error"])) $return_url .= "&rp_".$rp_index."_error=".urlencode($rp_data["error"]);
        $return_url .= "&rp_".$rp_index."_profile_id=".urlencode($rp_data["profile_id"]);
        $return_url .= "&rp_".$rp_index."_status=".urlencode($rp_data["status"]);
        $return_url .= "&rp_".$rp_index."_first_payment_date=".$rp_data["first_payment_date"];
        $return_url .= "&rp_".$rp_index."_signature=".urlencode($rp_signature);
    }

    return $return_url;
}

function trialDays($date){
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', $date);

    $date1 = date_create($start_date);
    $date2 = date_create($end_date);

    $diff = date_diff($date1,$date2);

    return $diff->format("%a");
}

function Amount($amount){


    if (strpos($amount, '.') !== false) {
        $expl = explode('.', $amount);
        $amount = $expl[0];
    }

    $amount .= '00';
    return (int)$amount;
}