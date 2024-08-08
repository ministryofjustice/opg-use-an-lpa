<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\ApiGateway\AbstractApiClient;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use DateTimeImmutable;
use GuzzleHttp\Client;
use App\DataAccess\Repository\Response\LpaInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class DataStoreLpas extends AbstractApiClient implements LpasInterface
{
    public function __construct(
        Client $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        RequestSignerFactory $requestSignerFactory,
        string $apiBaseUri,
        string $traceId,
        private readonly DataSanitiserStrategy $sanitiser,
    ) {
       parent::__construct(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $requestSignerFactory,
            $apiBaseUri,
            $traceId,
        );
    }

    /**
     * Looks up a Modernise LPA based on its uid.
     *
     * @param string $uid
     *
     * @return LpaInterface|null
     * @throws Exception
     */
    public function get(string $uid): ?LpaInterface
    {
        $url = $this->apiBaseUri . "/lpa/$uid";  // Update this to the correct endpoint for modernise LPAs

        $signer = ($this->requestSignerFactory)(SignatureType::DataStoreLpas);

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $signer->sign($this->attachHeaders($request));

        $response = $this->httpClient->sendRequest($request);

        $data = $this->sanitiser->sanitise(json_decode($response->getBody()->getContents(), true));

        return new Response\Lpa($data, new DateTimeImmutable($response->getHeaderLine('Date')));
    }

    public function lookup(array $uids): array
    {
        $url = $this->apiBaseUri . "/lpas";

        $signer = ($this->requestSignerFactory)(SignatureType::DataStoreLpas);

        $request = $this->requestFactory->createRequest('POST', $url)
                        ->withBody($this->streamFactory->createStream(json_encode([ 'uids' => $uids ])));

        $request = $signer->sign($this->attachHeaders($request));

        $response = $this->httpClient->sendRequest($request);

        $data = $this->sanitiser->sanitise(json_decode($response->getBody()->getContents(), true)['lpas']);

        return array_map(
            fn ($lpaData) => new Response\Lpa($lpaData, new DateTimeImmutable($response->getHeaderLine('Date'))),
            $data
        );
    }
}
