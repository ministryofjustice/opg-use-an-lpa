<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Exception\ApiException;
use Http\Client\Exception as HttpException;

/**
 * Class Client
 * @package App\Service\ApiClient
 */
interface ClientInterface
{
    /**
     * Performs a GET against the API
     *
     * @param string $path
     * @param array $query
     * @return array
     * @throws ApiException
     */
    public function httpGet(string $path, array $query = []): ?array;

    /**
     * Performs a POST against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPost(string $path, array $payload = []): array;

    /**
     * Performs a PUT against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPut(string $path, array $payload = []): array;

    /**
     * Performs a PATCH against the API
     *
     * @param string $path
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    public function httpPatch(string $path, array $payload = []): array;

    /**
     * Performs a DELETE against the API
     *
     * @param string $path
     * @return array
     * @throws ApiException
     */
    public function httpDelete(string $path): array;
}