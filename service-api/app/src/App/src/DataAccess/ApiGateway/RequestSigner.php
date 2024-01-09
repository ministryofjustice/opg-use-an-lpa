<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use Psr\Http\Message\RequestInterface;

class RequestSigner
{
    public function __construct(private SignatureV4 $signer, private ?string $staticAuthToken = null)
    {
    }

    public function sign(RequestInterface $request): RequestInterface
    {
        if ($this->staticAuthToken !== null) {
            return $request->withAddedHeader('Authorization', $this->staticAuthToken);
        }

        $provider    = CredentialProvider::defaultProvider();
        $credentials = $provider()->wait();

        $this->signer->region = 'eu-west-2';

        return $this->signer->signRequest($request, $credentials);
    }
}
