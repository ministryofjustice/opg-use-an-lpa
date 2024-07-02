<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use Psr\Http\Message\RequestInterface;

class RequestSigner
{
    private readonly array $headers;

    private Credentials $credentials;

    public function __construct(readonly private SignatureV4 $signer, array $headers = [])
    {
        $this->headers = array_filter($headers);

        $provider          = CredentialProvider::defaultProvider();
        $this->credentials = $provider()->wait();
    }

    public function sign(RequestInterface $request): RequestInterface
    {
        foreach ($this->headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $this->signer->signRequest($request, $this->credentials);
    }
}
