<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages as InstructionsAndPreferencesImagesDTO;
use App\Enum\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class InstructionsAndPreferencesImages extends AbstractApiClient implements InstructionsAndPreferencesImagesInterface
{
    public function getInstructionsAndPreferencesImages(int $lpaId): InstructionsAndPreferencesImagesDTO
    {
        $response = $this->makeGetRequest(
            'v1/image-request/' . $lpaId
        );

        $responseData = json_decode((string) $response->getBody());

        return new InstructionsAndPreferencesImagesDTO(
            (int) $responseData->uId,
            InstructionsAndPreferencesImagesResult::from((string) $responseData->status),
            (array) $responseData->signedUrls,
        );
    }

    /**
     * @throws ApiException|Exception
     */
    private function makeGetRequest(string $url): ResponseInterface
    {
        $url     = sprintf('%s/%s', $this->apiBaseUri, $url);
        $request = $this->requestFactory->createRequest('GET', $url);

        $request = $this->attachHeaders($request);
        $request = ($this->requestSignerFactory)()->sign($request);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $ce) {
            throw ApiException::create(
                'Error whilst communicating with instructions and preferences images service',
                null,
                $ce,
            );
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw ApiException::create(
                'Instructions and Preferences Images service returned non-ok response',
                $response,
            );
        }

        return $response;
    }
}
