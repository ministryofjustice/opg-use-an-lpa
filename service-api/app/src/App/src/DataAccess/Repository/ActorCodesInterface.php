<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\ActorCodeExists;
use App\DataAccess\Repository\Response\ActorCodeIsValid;
use App\DataAccess\Repository\Response\ResponseInterface;

interface ActorCodesInterface
{
    /**
     * Checks the provided information against the upstream source and returns
     * data that identifies the actor that information is registered against.
     *
     * @psalm-return ResponseInterface<ActorCodeIsValid>
     */
    public function validateCode(string $code, string $uid, string $dob): ResponseInterface;

    /**
     * Marks a given actor code as used. It will not be able to be used again.
     */
    public function flagCodeAsUsed(string $code): void;

    /**
     * @psalm-return ResponseInterface<ActorCodeExists>
     */
    public function checkActorHasCode(string $lpaId, string $actorId): ResponseInterface;
}
