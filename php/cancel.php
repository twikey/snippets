<?php
    include_once "common.php";

    $data = new StdClass();
    foreach($_GET as $key => $value)
    {
       $data->{$key} = $value;
    }

    $cancel = $twikey->cancelMandate($data);
    var_dump($cancel);
    die();
?>