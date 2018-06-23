<?php
    include_once "common.php";

    $payments = $twikey->getPayments( $_GET['id'], $_GET['detail']);
    foreach($payments as $key => $value){
        foreach($value as $key1 => $value1){
            foreach($value1->Entries as $key2 => $value2){
                echo "<p>transaction with ID " .$value2->txid ." has status: " .$value2->state ."</p>";
            }
        }
    }
?>