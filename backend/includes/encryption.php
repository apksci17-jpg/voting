<?php
// includes/encryption.php — Ballot Encryption Helper

define('BALLOT_ENCRYPTION_KEY', 'TomorrowVote2026SecureBallotSecretKey!#89');

/**
 * Encrypt ballot payload using AES-256-CBC
 */
function encryptBallot($data) {
    $key = hash('sha256', BALLOT_ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(16);
    $json = is_array($data) ? json_encode($data) : $data;
    $encrypted = openssl_encrypt($json, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt ballot payload using AES-256-CBC
 */
function decryptBallot($encryptedBase64) {
    $key = hash('sha256', BALLOT_ENCRYPTION_KEY, true);
    $raw = base64_decode($encryptedBase64);
    if (strlen($raw) < 17) {
        return null;
    }
    $iv = substr($raw, 0, 16);
    $cipherText = substr($raw, 16);
    $decrypted = openssl_decrypt($cipherText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return json_decode($decrypted, true) ?: $decrypted;
}
