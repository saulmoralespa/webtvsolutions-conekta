<?php

//enviroment conekta
//change a false para production
$sandbox = true;

$apiKeyPublicSandbox = 'fsdgsdgdhgdfgdhklcabg';
$apiKeyPrivateSandbox = 'sfdgdy56udgfgnhsdg';

$apiKeyPublic = 'fsdgsdgdhgdfgdhklcabg';
$apiKeyPrivate = 'sfdgdy56udgfgnhsdg';


if ($sandbox){
    $keyPrivate = $apiKeyPrivateSandbox;
    $keyPublic = $apiKeyPublicSandbox;
}else{
    $keyPrivate = $apiKeyPrivate;
    $keyPublic = $apiKeyPublic;
}



// this key must match the key entered in the external payment processor configuration of the Store extension (Store > Configuration). Keep it SAFE!.
$key_signature = "w564yge$%&";
// use https if you have enabled it in the WebTV; otherwise, use http
$webtv_base_url = "https://www.mywebtvdomain.com/";