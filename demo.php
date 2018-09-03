<?php

echo Amount(5);

function Amount($amount){

    $number = (int)$amount;

    if ($number === 0)
        return $number;
    $number .= "00";
    return (int)$number;
}