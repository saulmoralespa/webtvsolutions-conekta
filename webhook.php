<?php

if(!empty($_POST)){
    $body = @file_get_contents('php://input');
    $data = json_decode($body);
    http_response_code(200);

}