package crypto

import (
	"crypto/aes"
	"crypto/cipher"
	"crypto/rand"
	"encoding/base64"
	"errors"
	"os"
)

var encryptionKey []byte

func Init() error {
	keyStr := os.Getenv("QUERYBASE_ENCRYPTION_KEY")
	if keyStr == "" {
		return errors.New("QUERYBASE_ENCRYPTION_KEY nao definida")
	}

	key, err := base64.StdEncoding.DecodeString(keyStr)
	if err != nil {
		return errors.New("QUERYBASE_ENCRYPTION_KEY invalida: deve ser base64")
	}

	if len(key) != 32 {
		return errors.New("QUERYBASE_ENCRYPTION_KEY deve ter 32 bytes (256 bits)")
	}

	encryptionKey = key
	return nil
}

func Decrypt(encryptedBase64 string) (string, error) {
	if len(encryptionKey) == 0 {
		return "", errors.New("chave de criptografia nao inicializada")
	}

	data, err := base64.StdEncoding.DecodeString(encryptedBase64)
	if err != nil {
		return "", errors.New("dados criptografados invalidos")
	}

	if len(data) < 12 {
		return "", errors.New("dados criptografados muito curtos")
	}

	nonce := data[:12]
	ciphertext := data[12:]

	block, err := aes.NewCipher(encryptionKey)
	if err != nil {
		return "", err
	}

	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return "", err
	}

	plaintext, err := gcm.Open(nil, nonce, ciphertext, nil)
	if err != nil {
		return "", errors.New("falha ao descriptografar: chave incorreta ou dados corrompidos")
	}

	return string(plaintext), nil
}

func Encrypt(plaintext string) (string, error) {
	if len(encryptionKey) == 0 {
		return "", errors.New("chave de criptografia nao inicializada")
	}

	block, err := aes.NewCipher(encryptionKey)
	if err != nil {
		return "", err
	}

	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return "", err
	}

	nonce := make([]byte, gcm.NonceSize())
	if _, err := rand.Read(nonce); err != nil {
		return "", err
	}

	ciphertext := gcm.Seal(nonce, nonce, []byte(plaintext), nil)

	return base64.StdEncoding.EncodeToString(ciphertext), nil
}
