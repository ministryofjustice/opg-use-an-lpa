<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\ViewerCodesInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTime;

class ViewerCodes implements ViewerCodesInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $viewerCodesTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $viewerCodesTable
     */
    public function __construct(DynamoDbClient $client, string $viewerCodesTable)
    {
        $this->client = $client;
        $this->viewerCodesTable = $viewerCodesTable;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->viewerCodesTable,
            'Key' => [
                'ViewerCode' => [
                    'S' => $code,
                ],
            ],
        ]);

        $codeData = $this->getData($result, ['Added','Expires','Cancelled']);

        return !empty($codeData) ? $codeData : null;
    }

    /**
     * @inheritDoc
     */
    public function getCodesByUserLpaActorId(string $siriusUid, string $userLpaActor): array
    {
        $marshaler = new Marshaler();

        $result = $this->client->query([
            'TableName' => $this->viewerCodesTable,
            'IndexName' => 'SiriusUidIndex',
            'KeyConditionExpression' => 'SiriusUid = :uId',
            'FilterExpression' => 'UserLpaActor = :actor',
            'ExpressionAttributeValues' => $marshaler->marshalItem([
                ':uId' => $siriusUid,
                ':actor' => $userLpaActor
            ]),
        ]);

        if ($result['Count'] !== 0) {
            $accessCodes = $this->getDataCollection($result);
            return $accessCodes;
        } else {
            //the user has not yet created any access codes
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function add(string $code, string $userLpaActorToken, string $siriusUid, DateTime $expires, string $organisation)
    {
        // The current DateTime, including microseconds
        $now = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');

        try {
            $this->client->putItem([
                'TableName' => $this->viewerCodesTable,
                'Item' => [
                    'ViewerCode'    => ['S' => $code],
                    'UserLpaActor'  => ['S' => $userLpaActorToken],
                    'SiriusUid'     => ['S' => $siriusUid],
                    'Added'         => ['S' => $now],
                    'Expires'       => ['S' => $expires->format('c')],  // We use 'c' so not to assume UTC.
                    'Organisation'  => ['S' => $organisation],
                ],
                'ConditionExpression' => 'attribute_not_exists(ViewerCode)'
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                throw new KeyCollisionException();
            }
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $code, DateTime $cancelledDate): bool
    {
        //  Update the item by cancelling the code and setting cancelled date
        $this->client->updateItem([
            'TableName' => $this->viewerCodesTable,
            'Key' => [
                'ViewerCode' => [
                    'S' => $code,
                ],
            ],
            'UpdateExpression' => 'SET Cancelled=:c',
            'ExpressionAttributeValues'=> [
                ':c' => [
                    'S' => $cancelledDate->format('c')
                ]
            ]
        ]);

        return true;
    }
}
