<?php

declare(strict_types=1);

namespace CommonTest\Service\Notify;

use Common\Service\ApiClient\Client;
use Common\Service\Notify\NotifyService;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class NotifyServiceTest extends TestCase
{
    use ProphecyTrait;

    private string $defaultLocale;

    private const EN_LOCALE = 'en_GB';
    private const CY_LOCALE = 'cy_GB';

    /**
     * @test
     */
    public function can_send_email_to_user()
    {
        $this->defaultLocale = self::CY_LOCALE;
        $loggerProphecy      = $this->prophesize(LoggerInterface::class);
        $apiClientProphecy   = $this->prophesize(Client::class);

        $emailTemplate      = 'AccountActivationEmail';
        $activateAccountUrl = 'http://localhost:9002/cy/activate-account/8tjX_FtUzTrKc9ZtCk8HIQgczYLSX1Ys5paeNjuQFsE=';
        $result             = new JsonResponse([]);

        $apiClientProphecy->httpPost(
            '/v1/email-user/' . $emailTemplate,
            [
                'recipient'          => 'test@example.com',
                'locale'             => self::CY_LOCALE,
                'activateAccountUrl' => $activateAccountUrl,
            ]
        )
            ->willReturn([]);

        $service = new NotifyService($apiClientProphecy->reveal(), $loggerProphecy->reveal());

        $result = $service->sendEmailToUser(
            $emailTemplate,
            'test@example.com',
            activateAccountUrl: $activateAccountUrl
        );

        $this->assertTrue($result);
    }
}
