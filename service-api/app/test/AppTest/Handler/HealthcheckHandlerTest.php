<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\Repository\LpasInterface;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Handler\HealthcheckHandler;
use Zend\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    private $dbClientProphecy;

    private $lpaInterface;

    protected function setUp()
    {
        $this->dbClientProphecy = $this->prophesize(DynamoDbClient::class);
        $this->lpaInterface = $this->prophesize(LpasInterface::class);
    }

    /** @test */
    public function testReturnsExpectedJsonResponse()
    {
        $version = 'dev';

        $tableNames = ["ActorCodes", "ActorUsers", "UserLpaActorMap", "ViewerActivity", "ViewerCodes"];

        $this->lpaInterface->get("700000000000")
            ->willReturn(null);

        $this->dbClientProphecy->listTables()->willReturn([
            "TableNames" => $tableNames
        ]);

        $healthcheck = new HealthcheckHandler($version, $this->dbClientProphecy->reveal(), $this->lpaInterface->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $healthcheck->handle($requestProphecy->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertArrayHasKey('healthy', $json);
        $this->assertArrayHasKey('version', $json);
        $this->assertArrayHasKey('dependencies', $json);

        $dependencies = $json['dependencies'];
        $this->assertArrayHasKey('api', $dependencies);
        $this->assertArrayHasKey('dynamo', $dependencies);

        $api = $dependencies['api'];
        $dynamo = $dependencies['dynamo'];
        $this->assertArrayHasKey('healthy', $api);
        $this->assertArrayHasKey('healthy', $dynamo);

        $this->assertEquals(true, $api['healthy']);
        $this->assertEquals(true, $dynamo['healthy']);
        $this->assertEquals(true, $json['healthy']);
    }
}
