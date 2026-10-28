<?php
function sendMail($to, $subject, $message) {
    $headers = "From: no-reply@example.com";

    if(mail($to, $subject, $message, $headers)) {
        return true;
    } else {
        return false;
    }
}
?>