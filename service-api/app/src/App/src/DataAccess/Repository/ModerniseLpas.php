<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Service\Log\RequestTracing;
use DateTimeImmutable;
use GuzzleHttp\Client;
use App\DataAccess\Repository\Response\LpaInterface;
use Psr\Http\Message\RequestInterface;

class ModerniseLpas implements LpasInterface
{
    public function __construct(
        private Client $client,
        private string $traceId,
        private DataSanitiserStrategy $sanitiser,
        private string $endpoint,
    ){
    }

    private function attachHeaders(RequestInterface $request): RequestInterface
    {
        if (!empty($this->traceId)) {
            $request = $request->withHeader(RequestTracing::TRACE_HEADER_NAME, $this->traceId);
        }

        return $request;
    }

    public function get(string $uid): ?LpaInterface
    {
        $url = $this->endpoint . "/lpa/$uid";  // Update this to the correct endpoint for modernise LPAs

        $request = $this->client->createRequest('GET', $url);
        $request = $this->attachHeaders($request);

        $response = $this->client->sendRequest($request);
        $data = $this->sanitiser->sanitise(json_decode($response->getBody()->getContents(), true));

        return new Response\Lpa($data, new DateTimeImmutable($response->getHeaderLine('Date')));
    }

    public function lookup(array $uids): array
    {
        // TODO

        return [];
    }
}
