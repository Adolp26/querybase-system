<?php

namespace App\Services;

class EncryptionService
{
    private string $key;
    private string $cipher = 'aes-256-gcm';
    private int $nonceLength = 12;
    private int $tagLength = 16;

    public function __construct()
    {
        $keyBase64 = config('querybase.encryption_key');

        if (empty($keyBase64)) {
            throw new \RuntimeException('QUERYBASE_ENCRYPTION_KEY nao configurada');
        }

        $this->key = base64_decode($keyBase64);

        if (strlen($this->key) !== 32) {
            throw new \RuntimeException('QUERYBASE_ENCRYPTION_KEY deve ter 32 bytes (256 bits)');
        }
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes($this->nonceLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            $this->tagLength
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha ao criptografar');
        }

        return base64_encode($nonce . $ciphertext . $tag);
    }

    public function decrypt(string $encryptedBase64): string
    {
        $data = base64_decode($encryptedBase64);

        if ($data === false || strlen($data) < $this->nonceLength + $this->tagLength) {
            throw new \RuntimeException('Dados criptografados invalidos');
        }

        $nonce = substr($data, 0, $this->nonceLength);
        $tag = substr($data, -$this->tagLength);
        $ciphertext = substr($data, $this->nonceLength, -$this->tagLength);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Falha ao descriptografar');
        }

        return $plaintext;
    }
}
