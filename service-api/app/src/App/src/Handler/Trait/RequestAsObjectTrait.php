<?php

declare(strict_types=1);

namespace App\Handler\Trait;

use App\Exception\BadRequestException;
use App\Middleware\RequestObject\RequestObjectMiddleware;
use App\Request\InputFilteredRequest;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template T as InputFilteredRequest
 * @codeCoverageIgnore
 */
trait RequestAsObjectTrait
{
    /**
     * @param ServerRequestInterface $request
     * @param class-string<T>        $class
     * @return T
     * @throws BadRequestException
     */
    public function requestAsObject(ServerRequestInterface $request, string $class): object
    {
        return $request
            ->getAttribute(RequestObjectMiddleware::REQUEST_OBJECT)
            ->get($class);
    }
}
