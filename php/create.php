<?php
    include_once "common.php";

    $data =  array(
        "ct" => 1, // see Settings > Template 
        "email" => "john@doe.com",
        "firstname" => "John",
        "lastname" => "Doe",
        "l" => "en",
        "address" => "Abbey road",
        "city" => "Liverpool",
        "zip" => "1526",
        "country" => "BE",
        "mobile" => "",
        "companyName" => "",
        "form" => "",
        "vatno" => "",
        "iban" => "",
        "bic" => "",
        "mandateNumber" => "",
        "contractNumber" => "",
    );

    $contract = $twikey->createNew($data);
    var_dump($contract);
    header('Location: '.$contract->url);
    die();
?>