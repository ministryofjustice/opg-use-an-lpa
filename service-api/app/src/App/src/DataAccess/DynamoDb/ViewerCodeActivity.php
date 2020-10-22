<?php

declare(strict_types=1);

namespace App\DataAccess\DynamoDb;

use App\DataAccess\Repository\ViewerCodeActivityInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use DateTime;

class ViewerCodeActivity implements ViewerCodeActivityInterface
{
    use DynamoHydrateTrait;

    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * @var string
     */
    private $viewerActivityTable;

    /**
     * ViewerCodeActivity constructor.
     * @param DynamoDbClient $client
     * @param string $viewerActivityTable
     */
    public function __construct(DynamoDbClient $client, string $viewerActivityTable)
    {
        $this->client = $client;
        $this->viewerActivityTable = $viewerActivityTable;
    }

    /**
     * @inheritDoc
     */
    public function recordSuccessfulLookupActivity(string $activityCode, string $organisation): void
    {
        // The current DateTime, including microseconds
        $now = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');

        $this->client->putItem([
            'TableName' => $this->viewerActivityTable,
            'Item' => [
                'ViewerCode'    => ['S' => $activityCode],
                'Viewed'        => ['S' => $now],
                'ViewedBy'      => ['S' => $organisation]
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getStatusesForViewerCodes(array $viewerCodes): array
    {
        $marshaler = new Marshaler();

        foreach ($viewerCodes as $key => $code) {
            $result = $this->client->query([
                'TableName' => $this->viewerActivityTable,
                'KeyConditionExpression' => 'ViewerCode = :code',
                'ExpressionAttributeValues' => $marshaler->marshalItem([
                    ':code' => $code['ViewerCode']
                ]),
            ]);

            if ($result['Count'] === 0) {
                $viewerCodes[$key]['Viewed'] = false;
            } else {
                $viewerActivityDetails = $this->getDataCollection($result);
                $viewerCodes[$key]['Viewed'] = $viewerActivityDetails;
            }
        }

        return $viewerCodes;
    }
}
