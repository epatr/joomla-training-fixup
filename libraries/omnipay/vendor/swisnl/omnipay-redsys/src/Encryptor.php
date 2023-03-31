<?php

namespace Omnipay\RedSys;

final class Encryptor
{
    private $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = base64_decode($secretKey);
    }

    public function encrypt($message)
    {
        $bytes = [0, 0, 0, 0, 0, 0, 0, 0];
        $iv = implode(array_map("chr", $bytes));

        return mcrypt_encrypt(MCRYPT_3DES, $this->secretKey, $message, MCRYPT_MODE_CBC, $iv);
    }
}
