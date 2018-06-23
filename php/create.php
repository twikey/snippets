<?php
    include_once "common.php";

    $data = new StdClass();
    foreach($_GET as $key => $value)
    {
       $data->{$key} = $value;
    }

    $contract = $twikey->createNew($data);
    var_dump($contract);
    header('Location: '.$contract->url);
    die();
?>