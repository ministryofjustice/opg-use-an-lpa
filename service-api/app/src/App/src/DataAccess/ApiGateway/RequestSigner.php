<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Psr\Http\Message\RequestInterface;

class RequestSigner
{
    private ?string $staticAuthToken;

    private SignatureV4 $signer;

    public function __construct(SignatureV4 $signer, ?string $staticAuthToken = null)
    {
        $this->signer = $signer;
        $this->staticAuthToken = $staticAuthToken;
    }

    public function sign(RequestInterface $request): RequestInterface
    {
        if ($this->staticAuthToken !== null) {
            return $request->withAddedHeader('Authorization', $this->staticAuthToken);
        }

        $provider    = CredentialProvider::defaultProvider();
        $credentials = $provider()->wait();

        return $this->signer->signRequest($request, $credentials);
    }
}
