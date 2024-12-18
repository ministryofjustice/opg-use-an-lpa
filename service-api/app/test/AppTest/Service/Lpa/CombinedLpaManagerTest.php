<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Lpa\Combined\FilterActiveActors;
use App\Service\Lpa\Combined\ResolveLpaTypes;
use App\Service\Lpa\CombinedLpaManager;
use App\Service\Lpa\IsValidLpa;
use App\Service\Lpa\ResolveActor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;

class CombinedLpaManagerTest extends TestCase
{
    use ProphecyTrait;

    private CombinedLpaManager $combinedLpaManager;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapRepository;
    private ResolveLpaTypes|ObjectProphecy $resolveLpaTypes;
    private SiriusLpas|ObjectProphecy $siriusLpas;
    private DataStoreLpas|ObjectProphecy $dataStoreLpas;
    private ResolveActor|ObjectProphecy $resolveActor;
    private IsValidLpa|ObjectProphecy $isValidLpa;
    private FilterActiveActors|ObjectProphecy $filterActiveActors;
    public function setUp(): void
    {
        $this->userLpaActorMapInterfaceProphecy    = $this->prophesize(UserLpaActorMapInterface::class);
        $this->resolveLpaTypesProphecy    = $this->prophesize(ResolveLpaTypes::class);
        $this->siriusLpasProphecy    = $this->prophesize(SiriusLpas::class);
        $this->dataStoreLpasProphecy    = $this->prophesize(DataStoreLpas::class);
        $this->resolveActorProphecy    = $this->prophesize(ResolveActor::class);
        $this->isValidLpaProphecy    = $this->prophesize(IsValidLpa::class);
        $this->filterActiveActorsProphecy    = $this->prophesize(FilterActiveActors::class);
    }

      private function getLpaService(): CombinedLpaManager
      {
          return new CombinedLpaManager(
              $this->userLpaActorMapInterfaceProphecy->reveal(),
              $this->resolveLpaTypesProphecy->reveal(),
              $this->siriusLpasProphecy->reveal(),
              $this->dataStoreLpasProphecy->reveal(),
              $this->resolveActorProphecy->reveal(),
              $this->isValidLpaProphecy->reveal(),
              $this->filterActiveActorsProphecy->reveal(),
          );
      }

    #[Test]
    public function can_get_by_uid_test() {
        // TODO lpamanager needs to have a lpa
        // check when we get it by uid that we get that lpa with that uid
        $service = $this->getLpaService();
        $this->assertInstanceOf(CombinedLpaManager::class, $service);
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_test() {
    }

    #[Test]
    public function can_get_all_active_for_user_test() {
    }

    #[Test]
    public function can_get_all_for_user_test() {
    }

    #[Test]
    public function can_get_by_viewer_code_test() {
    }
}