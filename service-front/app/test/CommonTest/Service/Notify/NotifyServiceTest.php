<?php

declare(strict_types=1);

namespace CommonTest\Service\Notify;

use Common\Service\Notify\NotifyService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Log\LoggerInterface;
use Common\Service\ApiClient\Client;
use PHPUnit\Framework\TestCase;
use Locale;

class NotifyServiceTest extends TestCase
{
    private string $defaultLocale;

    private const EN_LOCALE = 'en_GB';
    private const CY_LOCALE = 'cy_GB';


    /** @test */
    public function can_send_email_to_user()
    {
        $this->defaultLocale = self::CY_LOCALE;
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $apiClientProphecy = $this->prophesize(Client::class);

        $emailTemplate = 'AccountActivationEmail';
        $result = new JsonResponse([]);

        $apiClientProphecy->httpPost(
            '/v1/email-user/' . $emailTemplate,
            [
                'recipient' => 'test@example.com',
                'locale' => self::CY_LOCALE,
                'parameter2' => 'http://localhost:9002/cy/activate-account/8tjX_FtUzTrKc9ZtCk8HIQgczYLSX1Ys5paeNjuQFsE=',
                'referenceNumber' => null,
                'postcode' => null,
                'letterExpectedDate' => null
            ]
        )
            ->willReturn([]);

        $service = new NotifyService($apiClientProphecy->reveal(), $loggerProphecy->reveal());

        $result = $service->sendEmailToUser(
            'test@example.com',
            'http://localhost:9002/cy/activate-account/8tjX_FtUzTrKc9ZtCk8HIQgczYLSX1Ys5paeNjuQFsE=',
            $emailTemplate,
            null,
            null,
            null
        );

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}