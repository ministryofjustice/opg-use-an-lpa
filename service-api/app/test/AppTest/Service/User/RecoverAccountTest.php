<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use App\Service\User\RecoverAccount;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
#[CoversClass(RecoverAccount::class)]
class RecoverAccountTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @psalm-param ActorUser $user
     * @psalm-param ActorUser|class-string<Exception> $existingUser
     * @psalm-param ?ActorUser $expects
     */
    #[Test]
    #[DataProvider('recoverableUserAccounts')]
    public function it_recovers_appropriately(
        array $user,
        array|string $existingUser,
        string $newEmail,
        array $foundLpas,
        ?array $expects,
    ): void {
        $actorUsersProphecy = $this->prophesize(ActorUsersInterface::class);

        $getByEmail = $actorUsersProphecy->getByEmail($newEmail);
        if (is_string($existingUser) && class_exists($existingUser)) {
            $getByEmail->willThrow($existingUser);
        } else {
            $getByEmail->willReturn($existingUser);
        }

        if ($expects !== null) {
            $actorUsersProphecy->delete($user['Id'])->willReturn($user);
            $actorUsersProphecy
                ->migrateToOAuth($existingUser, $user['Identity'])
                ->will(function () use ($existingUser, $user) {
                    $existingUser['Identity'] = $user['Identity'];
                    return $existingUser;
                });
        }

        $userActorLpaProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $userActorLpaProphecy
            ->getByUserId($user['Id'])
            ->willReturn($foundLpas);

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);

        $sut = new RecoverAccount(
            $actorUsersProphecy->reveal(),
            $userActorLpaProphecy->reveal(),
            $loggerInterfaceProphecy->reveal(),
        );

        $result = $sut($user, $newEmail);

        $this->assertSame($expects, $result);
    }

    public static function recoverableUserAccounts(): array
    {
        return [
            'It does not find an existing user and returns null'            => [
                'user'         => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'existingUser' => NotFoundException::class,
                'newEmail'     => 'fakeEmail',
                'foundLpas'    => [],
                'expects'      => null,
            ],
            'It sanity checks that the account is not the same'             => [
                'user'         => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'existingUser' => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'newEmail'     => 'fakeEmail',
                'foundLpas'    => [],
                'expects'      => null,
            ],
            'It does not recover from an account that has LPAs'             => [
                'user'         => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'existingUser' => [
                    'Id'       => 'originalFakeId',
                    'Identity' => 'originalFakeSub',
                    'Email'    => 'originalFakeEmail',
                ],
                'newEmail'     => 'originalFakeEmail',
                'foundLpas'    => [
                    700000000047 => [],
                    700000000138 => [],
                ],
                'expects'      => null,
            ],
            'It does not recover to an account that is already OIDC linked' => [
                'user'         => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'existingUser' => [
                    'Id'       => 'originalFakeId',
                    'Identity' => 'originalFakeSub',
                    'Email'    => 'originalFakeEmail',
                ],
                'newEmail'     => 'originalFakeEmail',
                'foundLpas'    => [],
                'expects'      => null,
            ],
            'It recovers to an account that has LPAs'                       => [
                'user'         => [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                    'Email'    => 'fakeEmail',
                ],
                'existingUser' => [
                    'Id'    => 'originalFakeId',
                    'Email' => 'originalFakeEmail',
                ],
                'newEmail'     => 'originalFakeId',
                'foundLpas'    => [],
                'expects'      => [
                    'Id'       => 'originalFakeId',
                    'Email'    => 'originalFakeEmail',
                    'Identity' => 'fakeSub',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('migrationFailures')]
    public function it_should_propagate_migration_failures_without_bypassing_the_pin(
        ConflictException|CreationException $error,
    ): void {
        $user     = [
            'Id'       => 'new-id',
            'Identity' => 'subject',
            'Email'    => 'old@example.com',
        ];
        $existing = [
            'Id'    => 'original-id',
            'Email' => 'user@example.com',
        ];

        $users = $this->createMock(ActorUsersInterface::class);
        $users->method('getByEmail')->with($existing['Email'])->willReturn($existing);
        $users->expects(self::once())->method('delete')->with($user['Id'])->willReturn($user);
        $users->expects(self::once())
            ->method('migrateToOAuth')
            ->willReturnCallback(function (
                array $account,
                string $identity,
                bool $ignoreOrphanIdentity = false,
            ) use (
                $existing,
                $error
            ): array {
                self::assertSame($existing, $account);
                self::assertSame('subject', $identity);
                self::assertFalse($ignoreOrphanIdentity);

                throw $error;
            });

        $lpas = $this->createStub(UserLpaActorMapInterface::class);
        $lpas->method('getByUserId')->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');

        $recover = new RecoverAccount($users, $lpas, $logger);

        $this->expectExceptionObject($error);

        $recover($user, $existing['Email']);
    }

    public static function migrationFailures(): array
    {
        return [
            'pin conflict'  => [new ConflictException('', ['identity' => 'subject'])],
            'other failure' => [new CreationException()],
        ];
    }
}
