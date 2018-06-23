<?php
    include_once "common.php";

    $trans = $twikey->getTransactionFeed();
    foreach($trans as $key => $value){
        foreach($value as $key1 => $value1){
            echo "<p>transaction with ID " .$value1->id ." has status: " .$value1->state ."</p>";
        }
    }
?>