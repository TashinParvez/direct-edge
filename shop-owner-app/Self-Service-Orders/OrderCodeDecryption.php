<?php
function decryptAES($encrypted)
{
    $key = "thisIsMyAESKey!!"; // same key

    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);

    return openssl_decrypt(
        $ciphertext,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}
