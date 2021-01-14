<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Psr\Log\LoggerInterface;

use function hash;

class UserIdentificationService
{
    private LoggerInterface $logger;

    /**
     * UserIdentificationService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Builds a unique userId that can be used to identify users for security tracking.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    public function id(\Psr\Http\Message\ServerRequestInterface $request): string
    {
        $headersToHash = [
            'accept' => '',
            'accept-encoding' => '',
            'accept-language' => '',
            'user-agent' => '',
            'x-forwarded-for' => ''
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
