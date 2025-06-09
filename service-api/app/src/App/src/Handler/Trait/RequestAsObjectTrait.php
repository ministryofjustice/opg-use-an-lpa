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
        try {
            return $request
                ->getAttribute(RequestObjectMiddleware::REQUEST_OBJECT)
                ->get($class);
        } catch (UnableToHydrateObject $exception) {
            if (count($exception->missingFields()) > 0) {
                throw new BadRequestException(
                    sprintf('%s requires %s', $class, implode(', ', $exception->missingFields())),
                    ['requestObject' => $class],
                    $exception
                );
            }

            throw new BadRequestException(
                'Could not hydrate request object',
                ['requestObject' => $class],
                $exception
            );
        }
    }
}
