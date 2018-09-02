<?php
header("Access-Control-Allow-Origin: *");

include ('config.php');
include_once ('status.php');
include_once ('cancel.php');
include ('header.php');

if (empty($_GET)){
    ?>
    <div class="alert alert-info">
        <strong>Info!</strong> No se estan recibiendo parametros
    </div>
    <?php
}else if(checkParamsGet() && !isset($_GET['action'])){
    showPayments(true);
}else if(checkParamsGet() && isset($_GET['action']) && isset($_GET['rp_num'])){
    showPayments();
}

function checkParamsGet(){

    global $key_signature;

    if (!isset($_GET["id_gateway"]))
        return false;
    if (!isset($_GET["id_order"]))
        return false;
    if (!isset($_GET["amount"]))
        return false;
    if (!isset($_GET["currency_code"]))
        return false;
    if (!isset($_GET["order_number"]))
        return false;
    if (!isset($_GET["signature"]))
        return false;

    $data = array(
        "id_gateway"    => (string)$_GET["id_gateway"],
        "id_order"      => (string)$_GET["id_order"],
        "amount"        => (string)$_GET["amount"],
        "currency_code" => $_GET["currency_code"],
        "order_number"  => (string)$_GET["order_number"]
    );
    $signature = rawurldecode($_GET["signature"]);
    $computed_signature = base64_encode(hash_hmac('sha256', json_encode($data), $key_signature, true));
    if ( $computed_signature != $signature)
        return false;
    return true;
}


function showPayments($cash = false){
    if ($cash) {
        ?>
        <div class="row">
            <div class="paymentCont">
                <div class="paymentWrap">
                    <div class="headingWrap">
                        <h3 class="headingTop text-center">Seleccione el medio de pago</h3>
                    </div>
                    <div class="btn-group paymentBtnGroup btn-group-justified" data-toggle="buttons">
                        <label class="btn paymentMethod" data-type="oxxo">
                            <div class="method oxxo" title="OXXO PAY"></div>
                            <input type="radio" name="options" checked>
                        </label>
                        <label class="btn paymentMethod" data-type="spei">
                            <div class="method spei" title="SPEI"></div>
                            <input type="radio" name="options">
                        </label>
                        <label class="btn paymentMethod" data-type="credit">
                            <div class="method credit" title="Pago con tarjeta"></div>
                            <input type="radio" name="options">
                        </label>
                    </div>
                </div>
            </div>
        </div>
           <?php formCredit(); ?>
        <div class="payent method-spei" style="display: none">
            <div class="flex-center">
                <form class="form-cash" class="col-md-6">
                    <input type="hidden" name="payment_method" value="spei">
                    <?php  inputRequiredCash(); ?>
                </form>
                <?php include_once ('templates/spei.php'); ?>
            </div>
        </div>
        <div class="payent method-oxxo" style="display: none">
            <div class="flex-center">
                <form class="form-cash" class="col-md-6">
                    <input type="hidden" name="payment_method" value="oxxo_cash">
                    <?php  inputRequiredCash(); ?>
                </form>
                <?php include_once ('templates/oxxo.php'); ?>
            </div>
        </div>
        <?php
    }else{
        formCredit(true);
    }
}

function inputRequiredCash(){
    ?>
    <input type="hidden" name="id_order" value="<?php echo $_GET["id_order"]; ?>">
    <input type="hidden" name="currency_code" value="<?php echo strtolower($_GET['currency_code']); ?>">
    <input type="hidden" name="amount" value="<?php echo $_GET["amount"]; ?>">
    <input type="hidden" name="order_number" value="<?php echo $_GET["order_number"]; ?>">
    <input type="hidden" name="payment" value="cash">
    <div class="form-group">
        <label for="name_cash">Nombre y apellidos</label>
        <input type="text" class="form-control" name="name_cash" id="name_cash" placeholder="Su nombre" required>
    </div>
    <div class="form-group">
        <label for="email_cash">Correo electrónico</label>
        <input type="email" class="form-control" name="email_cash" id="email_cash" placeholder="sunombre@domain.com" required>
    </div>
    <div class="form-group">
        <label for="phone_cash">Teléfono</label>
        <input type="tel" class="form-control" name="phone_cash" id="phone_cash" placeholder="5673465420" required>
    </div>
    <div class="form-group">
        <input class="btn btn-primary" type="submit" value="Pagar">
    </div>
<?php
}

function paramsCard($isSubscription = false)
{
    if ($isSubscription) {
        ?>
        <input type="hidden" name="id_order" value="<?php echo $_GET["id_order"]; ?>">
        <input type="hidden" name="currency_code" value="<?php echo strtolower($_GET['currency_code']); ?>">
        <input type="hidden" name="amount" value="<?php echo $_GET["amount"]; ?>">
        <input type="hidden" name="order_number" value="<?php echo $_GET["order_number"]; ?>">
        <input type="hidden" name="id_gateway" value="<?php echo $_GET["id_gateway"]; ?>">
        <input type="hidden" name="subscriptions" value='<?php echo json_encode(dataSubscription()); ?>'>
        <?php
    }else{
        ?>
        <input type="hidden" name="id_order" value="<?php echo $_GET["id_order"]; ?>">
        <input type="hidden" name="currency_code" value="<?php echo strtolower($_GET['currency_code']); ?>">
        <input type="hidden" name="amount" value="<?php echo $_GET["amount"]; ?>">
        <input type="hidden" name="order_number" value="<?php echo $_GET["order_number"]; ?>">
        <input type="hidden" name="id_gateway" value="<?php echo $_GET["id_gateway"]; ?>">
        <?php
    }
}


function formCredit($subscription = false){
    ?>
    <div class="alert" style="display: none"></div>
    <div  class="payent method-credit" style="<?php if($subscription){ echo 'display: block'; }else{ echo 'display: none'; }  ?> ">
    <div class='card-wrapper'></div>
        <div class="flex-center">
            <form id="credit" class="col-md-6">
                <div class="form-group">
                    <input type="text" class="form-control" name="number" data-conekta="card[number]"  placeholder="4556561571489682" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="name" data-conekta="card[name]" placeholder="tarjetahabiente" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="expiry" placeholder="expiración" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="cvc" data-conekta="card[cvc]"  placeholder="cvc" required>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" name="email"  placeholder="sunombre@domain.com" required>
                </div>
                <?php paramsCard($subscription); ?>
                <div class="form-group">
                    <input class="btn btn-primary" type="submit" value="Pagar">
                </div>
            </form>
        </div>
    </div>
<?php
}


function dataSubscription(){

    global  $key_signature;

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
            $rp_computed_signature = base64_encode(hash_hmac('sha256', md5( $rp_sku . floatval($rp_amount) . $rp_period_frequency . $rp_period  )  , $key_signature, true));

            // If the generated signature is different than the received one then don't  process this item but mark it as failed!
            // The signature guarantees that the data has not been altered.
            // If the signatures don't match, and you are sure the data has not been altered,
            // then check if the key supplied to the WebTV is the same key you are using in this script
            if ( $rp_computed_signature!=$rp_signature)
            {
                return null;
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

    return $recurring_payments;
}
include ('footer.php');