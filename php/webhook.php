<?php
$provided_signature = $_SERVER['HTTP_X_SIGNATURE'];
$message = urldecode($_SERVER['QUERY_STRING']);

$apiKey = "C0EEE955DD2E2BDE3D42AB2B7EAF668C92899E1B";
$calculated = strtoupper(hash_hmac('sha256', $message, $apiKey));
error_log("Calculated: ".$calculated);
error_log("Given: ".$provided_signature);
error_log("Message: ".$message);
error_log("Same: ".($calculated==$provided_signature));

