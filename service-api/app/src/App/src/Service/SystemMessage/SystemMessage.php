<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

use Aws\Ssm\SsmClient;

class SystemMessage
{
    /**
     * @var string[] List of parameter names, without the /system-message/$environment prefix
     */
    private static array $PARAMETER_NAMES = [
        'use/en',
        'use/cy',
        'view/en',
        'view/cy',
    ];

    public function __construct(
        private SsmClient $ssmClient,
        private string $prefix,
    ) {
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    private function stripPrefix(string $parameterName): string
    {
        return substr($parameterName, strlen($this->prefix));
    }

    public function getSystemMessages(): array
    {
        $response = $this->ssmClient->getParameters(
            // Add prefix (e.g. '/system-message/<environment_name>') before each parameter name
            ['Names' => array_map(fn ($name) => $this->prefix . $name, self::$PARAMETER_NAMES)]
        );

        $parameters     = $response['Parameters'];
        $nameToValueMap = [];

        foreach ($parameters as $parameter) {
            // Remove prefixes from parameter names before we return them to the client
            // e.g. so UI doesn't need to care about understanding environment name, etc.
            $nameToValueMap[$this->stripPrefix($parameter['Name'])] = $parameter['Value'];
        }

        return $nameToValueMap;
    }
}
