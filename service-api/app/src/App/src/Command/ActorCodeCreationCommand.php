<?php

declare(strict_types=1);

namespace App\Command;

use App\DataAccess\ApiGateway\Lpas;
use App\DataAccess\DynamoDb\DynamoHydrateTrait;
use App\Service\ViewerCodes\CodeGenerator;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ActorCodeCreationCommand extends Command
{
    use DynamoHydrateTrait;

    /**
     * @var Lpas
     */
    private $apiGateway;
    /**
     * @var DynamoDbClient
     */
    private $client;
    /**
     * @var string
     */
    private $actorCodesTable;

    public function __construct(Lpas $apiGateway, DynamoDbClient $client, string $actorCodesTable)
    {
        parent::__construct();

        $this->apiGateway = $apiGateway;
        $this->client = $client;
        $this->actorCodesTable = $actorCodesTable;
    }

    protected function configure()
    {
        $this
            ->setName('actorcode:create')
            ->setDescription('Accepts a list of LPA uIds and outputs newly created actorcodes for all '
                . 'actors attached to those LPAs')
            ->addArgument(
                'lpas',
                InputArgument::REQUIRED,
                'A comma separated list of LPA uIds'
            )
            ->addOption(
                'dryrun',
                'd',
                InputOption::VALUE_NONE,
                'Generate and return actorcodes for LPA\'s but do not save them to DynamoDB'
            )
            ->addOption(
                'replace',
                'r',
                InputOption::VALUE_NONE,
                'Overwrite existing codes with new ones for actors already in the database'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $lpas = $this->splitLpas($input->getArgument('lpas'));

        $this->resolveLpaActors($lpas);

        $this->generateCodes($lpas);

        if (! $input->getOption('dryrun')) {
            $this->saveCodes($lpas, $input->getOption('replace'));
        }

        $output->writeln(json_encode($lpas));
    }

    private function splitLpas(string $lpas): array
    {
        $lpas = explode(',', $lpas);

        return array_combine(
            $lpas,
            array_map(function () {
                return [];
            },
            $lpas)
        );
    }

    private function resolveLpaActors(&$lpas): void
    {
        $lpaData = array_map(
            function ($lpaUId) {
                $lpa = $this->retrieveLpa((string) $lpaUId);

                return array_map(function ($actor) {
                    return array_intersect_key($actor, [
                        'id'          => '',
                        'uId'         => '',
                        'firstname'   => '',
                        'middlenames' => '',
                        'surname'     => ''
                    ]);
                }, array_merge([ $lpa['donor'] ], $lpa['attorneys']));
            },
            array_keys($lpas)
        );

        $lpas = array_combine(array_keys($lpas), $lpaData);
    }

    private function retrieveLpa(string $lpaUId): array
    {
        $lpa = $this->apiGateway->get($lpaUId);

        if (is_null($lpa)) {
            throw new InvalidArgumentException(sprintf('LPA with uId %s does not exist', $lpaUId));
        }

        return $lpa->getData();
    }

    private function generateCodes(&$lpas): void
    {
        $lpaData = array_map(
            function ($lpaData) {

                return array_map(
                    function ($lpaActor) {
                        return array_merge($lpaActor, [
                            'code' => CodeGenerator::generateCode(),
                            'expiry' => (new \DateTime('23:59:59 + 12 months'))->format(DATE_ATOM)
                        ]);
                    },
                    $lpaData
                );
            },
            $lpas
        );

        $lpas = array_combine(array_keys($lpas), $lpaData);
    }

    private function saveCodes(array &$lpas, bool $replace = false)
    {
        $lpaData = array_map(
            function ($lpaUId, $lpaData) use ($replace) {

                return array_filter(
                    array_map(
                        function ($lpaActor) use ($lpaUId, $replace) {

                            if (is_null($this->actorExists((string) $lpaUId, (string) $lpaActor['id'])) || $replace) {
                                $this->writeDBRecord($lpaActor['code'], (string) $lpaUId, $lpaActor['expiry'], $lpaActor['id']);
                                return $lpaActor;
                            }

                            return null;
                        },
                        $lpaData
                    )
                );
            },
            array_keys($lpas),
            $lpas
        );

        $lpas = array_combine(array_keys($lpas), $lpaData);
    }

    /**
     * Checks for the existence of an LPA/Actor combo in the DB and returns the Code if it exists.
     *
     * @param string $lpaUId
     * @param string $actorLpaId
     * @return string|null
     */
    private function actorExists(string $lpaUId, string $actorLpaId): ?string
    {
        $marshaler = new Marshaler();

        // this may be slow as we don't have an index on these field combinations so must scan
        $result = $this->client->scan([
            'TableName' => $this->actorCodesTable,
            'FilterExpression' => 'SiriusUid = :lpaUId AND ActorLpaId = :actorLpaId',
            'ExpressionAttributeValues' => $marshaler->marshalItem([
                ':lpaUId'     => $lpaUId,
                ':actorLpaId' => (int) $actorLpaId
            ]),
        ]);

        $usersData = $this->getDataCollection($result);

        if (! empty($usersData)) {
            return array_pop($usersData)['ActorCode'];
        }

        return null;
    }

    private function writeDBRecord(string $code, string $siriusUId, string $expires, int $actorLpaId): void
    {
        $marshaler = new Marshaler();

        // this may be slow as we don't have an index on these field combinations so must scan
        $result = $this->client->putItem([
            'TableName' => $this->actorCodesTable,
            'Item' => $marshaler->marshalItem([
                'ActorCode'  => $code,
                'SiriusUid'  => $siriusUId,
                'Active'     => true,
                'Expires'    => $expires,
                'ActorLpaId' => (int) $actorLpaId
            ]),
        ]);
    }
}
