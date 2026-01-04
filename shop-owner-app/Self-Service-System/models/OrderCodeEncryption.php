<?php
function encryptAES($plaintext)
{
    $key = "thisIsMyAESKey!!"; // 16 bytes = 128 bits
    $iv  = openssl_random_pseudo_bytes(16);

    $ciphertext = openssl_encrypt(
        $plaintext,
        "AES-128-CBC",
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    // Combine IV + ciphertext
    return base64_encode($iv . $ciphertext);
}
