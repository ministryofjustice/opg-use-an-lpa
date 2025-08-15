<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Service\Session\KeyManager\KeyManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Halite\Alerts\HaliteAlert;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class HaliteEncryptedCookie implements EncryptInterface
{
    public function __construct(
        private KeyManagerInterface $keyManager,
        private HaliteCrypto $crypto,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function encodeCookieValue(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $plaintext = json_encode($data);
        $key       = $this->keyManager->getEncryptionKey();

        return $key->getId() . '.' . Base64UrlSafe::encode($this->crypto->encrypt($plaintext, $key));
    }

    /**
     * @inheritDoc
     */
    public function decodeCookieValue(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        // unquote the value if necessary
        $data = preg_replace('/\\\\(.)|"/', '$1', $data);

        // Separate out the key ID and the data
        [$keyId, $payload] = explode('.', trim($data, '"'), 2);

        try {
            $key        = $this->keyManager->getDecryptionKey($keyId);
            $ciphertext = Base64UrlSafe::decode($payload);

            return json_decode($this->crypto->decrypt($ciphertext, $key), true);
        } catch (HaliteAlert $alert) {
            $this->logger->warning(
                'Unable to decrypt the provided cookie payload. {message}',
                [
                    'message' => $alert->getMessage(),
                ],
            );
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());
        }

        // Something went wrong. Restart the session.
        return [];
    }
}
