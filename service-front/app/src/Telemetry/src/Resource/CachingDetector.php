<?php

declare(strict_types=1);

namespace Telemetry\Resource;

use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceDetectorInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use Telemetry\Suppression;

/**
 * Resource detection (e.g. querying the ECS task metadata endpoint) happens on every
 * request. The result does not change for the lifetime of a container so is cached
 */
final class CachingDetector implements ResourceDetectorInterface
{
    private const CACHE_KEY_PREFIX = 'otel.resource.';
    private const CACHE_TTL        = 3600;

    public function __construct(
        private readonly string $name,
        private readonly ResourceDetectorInterface $detector,
    ) {
    }

    public function getResource(): ResourceInfo
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $this->name;
        $useCache = function_exists('apcu_enabled') && apcu_enabled();

        if ($useCache) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return ResourceInfo::create(Attributes::create($cached['attributes']), $cached['schemaUrl']);
            }
        }

        $resource = Suppression::run($this->detector->getResource(...));

        if ($useCache) {
            apcu_store(
                $cacheKey,
                [
                    'attributes' => $resource->getAttributes()->toArray(),
                    'schemaUrl'  => $resource->getSchemaUrl(),
                ],
                self::CACHE_TTL,
            );
        }

        return $resource;
    }
}
