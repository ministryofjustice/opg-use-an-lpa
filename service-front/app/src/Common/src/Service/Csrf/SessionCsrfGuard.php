<?php

declare(strict_types=1);

namespace Common\Service\Csrf;

use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Session\SessionInterface;

use function bin2hex;
use function random_bytes;

class SessionCsrfGuard implements CsrfGuardInterface
{
    /**
     * @var string
     */
    private $requestId;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $this->requestId = bin2hex(random_bytes(8));
    }

    /**
     * @inheritDoc
     */
    public function generateToken(string $keyName = '__csrf'): string
    {
        $tokens = $this->cleanupTokens($this->session->get($keyName, []));

        $newToken = bin2hex(random_bytes(16));
        $tokens[$newToken] = $this->requestId;

        $this->session->set($keyName, $tokens);
        return $this->formatHash($newToken, $this->requestId);
    }

    /**
     * @inheritDoc
     */
    public function validateToken(string $token, string $csrfKey = '__csrf'): bool
    {
        $storedTokens = $this->session->get($csrfKey, []);

        $tokenParts = $this->splitHash($token);
        $tokenId = $tokenParts['token'];

        return array_key_exists($tokenId, $storedTokens)
            && $token === $this->formatHash($tokenId, $storedTokens[$tokenId]);
    }

    protected function cleanupTokens(array $tokens): array
    {
        $currentRequestId = $this->requestId;

        return array_filter($tokens, function ($requestId) use ($currentRequestId) {
            return $requestId === $currentRequestId;
        });
    }

    protected function formatHash(string $token, string $requestId): string
    {
        return sprintf('%s-%s', $token, $requestId);
    }

    protected function splitHash(string $hash): array
    {
        $data = explode('-', $hash);

        return [
            'token' => $data[0] ?? null,
            'requestId' => $data[1] ?? null
        ];
    }
}
