<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Cache\SessionLpaCacheInterface;
use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\DataAccess\Repository\ViewerCodeActivityInterface;
use App\DataAccess\Repository\ViewerCodesInterface;
use App\Service\Lpa\Combined\FilterActiveActors;
use App\Service\Lpa\Combined\RejectInvalidLpa;
use App\Service\Lpa\IsValidLpa;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusLpaManager;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SiriusLpaManager::class)]
final class SiriusLpaManagerSessionCacheTest extends TestCase
{
    public static function sessionCacheLookups(): array
    {
        return [
            'cached dashboard'   => [
                'getAllActiveForUser',
                'session-one',
                true,
            ],
            'uncached dashboard' => [
                'getAllActiveForUser',
                'session-one',
                false,
            ],
            'no session'         => [
                'getAllActiveForUser',
                null,
                false,
            ],
            'all LPAs'           => [
                'getAllForUser',
                null,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('sessionCacheLookups')]
    public function it_should_delegate_lookups_to_the_session_cache_and_keep_account_lpas_read_from_db(
        string $method,
        ?string $sessionId,
        bool $cached,
    ): void {
        $logger = new NullLogger();
        $lpa    = new Lpa(new SiriusLpa(['uId' => 'uid'], $logger), new DateTimeImmutable());
        $map    = [
            'Id'        => 'token',
            'SiriusUid' => 'uid',
            'ActorId'   => 'actor',
            'Added'     => new DateTimeImmutable(),
        ];

        $maps = $this->createMock(UserLpaActorMapInterface::class);
        $maps->expects(self::exactly(2))
            ->method('getByUserId')
            ->with('user')
            ->willReturnOnConsecutiveCalls([$map], []);

        $repository = $this->createMock(LpasInterface::class);
        $repository->expects($cached ? self::never() : self::once())
            ->method('lookup')
            ->with(['uid'])
            ->willReturn(['uid' => $lpa]);

        $cache = $this->createMock(SessionLpaCacheInterface::class);
        $cache->expects(self::once())
            ->method('lookup')
            ->with(['uid'], $sessionId, self::callback('is_callable'))
            ->willReturnCallback(
                static function (array $uids, ?string $session, callable $lookup) use ($cached, $lpa): array {
                    return $cached ? ['uid' => $lpa] : $lookup($uids);
                },
            );

        $valid = $this->createStub(IsValidLpa::class);
        $valid->method('__invoke')->willReturn(true);

        $service = new SiriusLpaManager(
            $maps,
            $repository,
            $this->createStub(ViewerCodesInterface::class),
            $this->createStub(ViewerCodeActivityInterface::class),
            $this->createStub(InstructionsAndPreferencesImagesInterface::class),
            $this->createStub(ResolveActor::class),
            $valid,
            $this->createStub(FilterActiveActors::class),
            $this->createStub(RejectInvalidLpa::class),
            $logger,
            $cache,
        );
        $result  = $method === 'getAllForUser'
            ? $service->getAllForUser('user')
            : $service->getAllActiveForUser('user', $sessionId);

        self::assertCount(1, $result);
        self::assertSame($lpa->getData(), $result['token']['lpa']);
        self::assertSame([], $method === 'getAllForUser'
            ? $service->getAllForUser('user')
            : $service->getAllActiveForUser('user', $sessionId));
    }
}
