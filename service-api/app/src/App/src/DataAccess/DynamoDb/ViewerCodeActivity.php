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
    public function recordSuccessfulLookupActivity(string $activityCode) : void
    {
        // The current DateTime, including microseconds
        $now = (new DateTime)->format('Y-m-d\TH:i:s.u\Z');

        $this->client->putItem([
            'TableName' => $this->viewerActivityTable,
            'Item' => [
                'ViewerCode'    => ['S' => $activityCode],
                'Viewed'        => ['S' => $now],
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getActivityStatusesForViewerCodes(array $accessCodes) : array
    {
        $marshaler = new Marshaler();

        foreach($accessCodes as $key => $code){

            $result = $this->client->scan([
                'TableName' => $this->viewerActivityTable,
                'ProjectionExpression' => 'ViewerCode, Viewed',
                'FilterExpression' => 'ViewerCode = :code',
                'ExpressionAttributeValues'=> $marshaler->marshalItem([
                    ':code' => $code['ViewerCode']
                ]),
            ]);

            if ($result['Count'] === 0){
                $accessCodes[$key]['Viewed'] = "Not Viewed";
            } else {
                $accessCodes[$key]['Viewed'] = "Viewed";
            }

        }

        return !empty($accessCodes) ? $accessCodes : null;
    }
}
