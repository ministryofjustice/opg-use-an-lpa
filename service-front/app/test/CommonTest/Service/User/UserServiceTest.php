<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RuntimeException;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_delete_a_users_account(): void
    {
        $id    = '01234567-0123-0123-0123-012345678901';
        $email = 'a@b.com';

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpDelete('/v1/delete-account/' . $id)
            ->willReturn([
                'Id'        => $id,
                'Email'     => $email,
                'Password'  => password_hash('pa33w0rd123', PASSWORD_DEFAULT),
                'LastLogin' => null,
            ]);

        $service = new UserService($apiClientProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectNotToPerformAssertions();
        $service->deleteAccount($id);
    }

    #[Test]
    public function exception_thrown_when_api_gives_invalid_response_to_delete_account_request(): void
    {
        $id = '01234567-0123-0123-0123-012345678901';

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpDelete('/v1/delete-account/' . $id)
            ->willThrow(new ApiException(
                'HTTP: 500 - Unexpected API response',
                StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
            ));

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(RuntimeException::class);

        $service = new UserService($apiClientProphecy->reveal(), $loggerProphecy->reveal());

        $service->deleteAccount($id);
    }
}
