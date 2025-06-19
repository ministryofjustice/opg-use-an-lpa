<?php

declare(strict_types=1);

namespace App\Middleware\RequestObject;

use App\Exception\BadRequestException;
use App\Request\InputFilteredRequest;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Laminas\Form\Annotation\AttributeBuilder;

/**
 * @template T as InputFilteredRequest
 */
class RequestParser
{
    /** @var array<string, mixed>  */
    private array $requestData;

    public function __construct(
        private ObjectMapper $mapper,
        private AttributeBuilder $attributeBuilder,
    ) {
    }

    public function setRequestData(array $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    /**
     * @param class-string<T> $request
     * @psalm-return T
     * @throws BadRequestException
     */
    public function get(string $request): InputFilteredRequest
    {
        $form = $this->attributeBuilder->createForm($request);

        if (! $form->setData($this->requestData)->isValid()) {
            throw new BadRequestException('Invalid data provided for request', $form->getMessages());
        }

        try {
            return $this->mapper->hydrateObject(
                $request,
                $form->getData(),
            );
        } catch (UnableToHydrateObject $exception) {
            throw new BadRequestException($exception->getMessage(), [], $exception);
        }
    }
}
