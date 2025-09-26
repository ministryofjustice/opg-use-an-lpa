<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Exception\SessionEncryptionFailureException;
use Common\Service\Session\KeyManager\KeyManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function json_encode;
use function json_decode;
use function preg_replace;
use function preg_match;

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

        try {
            $plaintext = json_encode($data);
            $key       = $this->keyManager->getEncryptionKey();

            if ($plaintext === false) {
                // @codeCoverageIgnoreStart
                throw new SessionEncryptionFailureException('Unable to json encode session data');
                // @codeCoverageIgnoreEnd
            }

            return $key->getId() . '.' . $this->crypto->encrypt($plaintext, $key);
        } catch (Throwable $e) {
            $this->logger->critical(
                'Failed to encrypt users session data to a cookie',
                [
                    'identity' => $data['identity'] ?? null,
                ]
            );

            throw new SessionEncryptionFailureException('Encryption of cookie failed', 500, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function decodeCookieValue(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Unquote the value if necessary
        $data = preg_replace('/\\\\(.)|"/', '$1', $data) ?? '';

        // Grab the key and encrypted value. Both should be base64urlencoded.
        preg_match('/^([\w-]+=*)\.([\w-]+=*)$/', $data, $matches);
        if (count($matches) === 3) {
            list(, $keyId, $payload) = $matches; // Leading comma skips first array value

            try {
                $key = $this->keyManager->getDecryptionKey($keyId);

                return json_decode($this->crypto->decrypt($payload, $key), true);
            } catch (Throwable $alert) {
                $this->logger->warning(
                    'Unable to decrypt the provided cookie payload. {message}',
                    [
                        'message' => $alert->getMessage(),
                    ],
                );
            }
        }

        // Something went wrong. Restart the session.
        return [];
    }
}
