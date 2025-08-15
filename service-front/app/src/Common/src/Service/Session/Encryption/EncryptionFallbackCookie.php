<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

/**
 * A cookie encrypter that attempts various cookie encryption variants.
 *
 * This allows us to change the variant in use whilst retaining the ability to decrypt
 * user session cookies that use an older variant. This situation should only last a couple of hours
 * but we ideally don't want to kick people out if we can help it.
 */
readonly class EncryptionFallbackCookie implements EncryptInterface
{
    /**
     * @var EncryptInterface[]
     */
    private array $previous;

    public function __construct(private EncryptInterface $current, EncryptInterface ...$previous)
    {
        $this->previous = $previous;
    }

    /**
     * @inheritDoc
     */
    public function encodeCookieValue(array $data): string
    {
        return $this->current->encodeCookieValue($data);
    }

    /**
     * @inheritDoc
     */
    public function decodeCookieValue(string $data): array
    {
        $cookieData = $this->current->decodeCookieValue($data);

        if (empty($cookieData)) {
            foreach ($this->previous as $previous) {
                $cookieData = $previous->decodeCookieValue($data);

                if (!empty($cookieData)) {
                    break;
                }
            }
        }

        return $cookieData;
    }
}
