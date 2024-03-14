<?php

declare(strict_types=1);

namespace App\Service;

use Aws\Ssm\SsmClient;

class SystemMessage
{
    /**
     * TODO add 'environment' prefix?
     *
     * @var string[] List of parameter names.
     */
    private static array $PARAMETER_NAMES = [
        '/system-message/use/en',
        '/system-message/use/cy',
        '/system-message/view/en',
        '/system-message/view/cy',
    ];

    public function __construct(
        private SsmClient $ssmClient,
    ) {
    }

    public function getSystemMessages(): array
    {
        $response       = $this->ssmClient->getParameters(['Names' => self::$PARAMETER_NAMES]);
        $parameters     = $response['Parameters'];
        $nameToValueMap = [];

        foreach ($parameters as $parameter) {
            $nameToValueMap[$parameter['Name']] = $parameter['Value'];
        }

        return $nameToValueMap;
    }
}
