<?php
    include_once "common.php";

    $data = array(
        "mndtId" => "MndtRef",
        "rsn" => "Some reason",
    );

    $cancel = $twikey->cancelMandate($data);
    var_dump($cancel);
    die();
?>