<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use function hash;

class UserIdentificationService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Builds a unique userId that can be used to identify users for security tracking.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function id(ServerRequestInterface $request): string
    {
        $headersToHash = [
            'accept-encoding' => '',
            'accept-language' => '',
            'user-agent'      => '',
            'x-forwarded-for' => '',
        ];

        // pull each header value out (if it exists)
        foreach ($headersToHash as $header => $value) {
            $headersToHash[$header] =
                $request->hasHeader($header)
                    ? $request->getHeader($header)[0]
                    : $header;
        }

        $this->logger->debug(
            'Identity of incoming request built',
            ['prehash_id' => implode('', $headersToHash)]
        );

        return hash('sha256', implode('', $headersToHash));
    }
}
