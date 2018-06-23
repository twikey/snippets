<?php
    include_once "common.php";

    $data = new StdClass();
    $data->{"mndtId"} = $_GET['mndtId'];
    $data->{"date"} = $_GET['date'];
    $data->{"reqcolldt"} = $_GET['reqcolldt'];
    $data->{"message"} = $_GET['message'];
    $data->{"ref"} = $_GET['ref'];
    $data->{"amount"} = $_GET['amount'];
    $data->{"place"} = $_GET['place'];

    $trans = $twikey->newTransaction($data);
    echo "<p>your transactionID is: " .$trans->Entries[0]->id ."<br/> please use this to look up the status of the transaction</p>";
?>