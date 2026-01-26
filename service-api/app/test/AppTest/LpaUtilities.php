<?php

declare(strict_types=1);

namespace AppTest;

use App\DataAccess\Repository\Response\Lpa as LpaResponse;
use App\DataAccess\Repository\Response\LpaInterface;
use App\DataAccess\Repository\Response\ResponseInterface;
use App\DataAccess\Repository\Response\UpstreamResponse;
use App\Entity\Lpa;
use App\Service\Lpa\LpaDataFormatter;
use DateTimeImmutable;
use DateTimeInterface;

class LpaUtilities
{
    public static function lpaStoreResponseFixture(
        array $overwrite = [],
        DateTimeInterface $fetchDate = new DateTimeImmutable(),
    ): LpaInterface {
        return new LpaResponse(
            self::lpaStoreLpaFixture($overwrite),
            $fetchDate,
        );
    }

    public static function lpaStoreLpaFixture(array $overwrite = []): Lpa
    {
        $lpaData = json_decode(file_get_contents(__DIR__ . '/../fixtures/4000.json'), true);
        $lpaData = array_merge($lpaData, $overwrite);

        /** @var Lpa */
        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }

    public static function codesApiResponseFixture(
        mixed $response,
        DateTimeInterface $fetchDate = new DateTimeImmutable(),
    ): ResponseInterface {
        return new UpstreamResponse(
            $response,
            $fetchDate,
        );
    }
}
