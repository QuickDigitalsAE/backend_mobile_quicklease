<?php 
    $TransactionID = $_POST['TransactionID'];
    // Encode
    $encodedId = base64_encode($TransactionID); 
    header("Location: https://quicklease.ae/finalize/?vf=".$encodedId);
    exit;
?>