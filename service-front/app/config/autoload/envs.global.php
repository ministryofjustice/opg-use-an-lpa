<?php

declare(strict_types=1);

use Laminas\Cache\Storage\Adapter\Redis as RedisCache;

return [
    'application' => get_defaulted_env('CONTEXT'),
    'version'     => get_defaulted_env('CONTAINER_VERSION', 'dev'),
    'api'         => [
        'uri' => get_defaulted_env('API_SERVICE_URL'),
    ],
    'pdf'         => [
        'uri' => get_defaulted_env('PDF_SERVICE_URL'),
    ],
    'aws'         => [
        'region'  => get_defaulted_env('AWS_REGION', 'eu-west-1'),
        'version' => 'latest',
        'Kms'     => [
            'endpoint' => get_defaulted_env('AWS_ENDPOINT_KMS'),
        ],
    ],
    'notify'      => [
        'api' => [
            'key' => get_defaulted_env('NOTIFY_API_KEY'),
        ],
    ],
    'session'     => [
        // Time in seconds after which a session will expire.
        'expires'          => 60 * intval(get_defaulted_env('SESSION_EXPIRES', 20)),  // minutes

        // Time in seconds before a users session will expire
        // whereby a popup window will appear to warn them
        'expiry_warning'   => 60 * intval(get_defaulted_env('SESSION_EXPIRY_WARNING', 5)), // minutes
        'cookie_ttl'       => 60 * intval(get_defaulted_env('SESSION_COOKIE_LIFETIME', 1440)), // one day
        'key'              => [
            // KMS alias to use for data key generation.
            'alias' => get_defaulted_env('KMS_SESSION_CMK_ALIAS'),
        ],

        // The name of the session cookie. This name must comply with
        // the syntax outlined in https://tools.ietf.org/html/rfc6265.html
        'cookie_name'      => '__Host-session',

        // The (sub)domain that the cookie is available to. Setting this
        // to a subdomain (such as 'www.example.com') will make the cookie
        // available to that subdomain and all other sub-domains of it
        // (i.e. w2.www.example.com). To make the cookie available to the
        // whole domain (including all subdomains of it), simply set the
        // value to the domain name ('example.com', in this case).
        // Leave this null to use browser default (current hostname).
        'cookie_domain'    => null,

        // The path prefix of the cookie domain to which it applies.
        'cookie_path'      => '/',

        // Indicates that the cookie should only be transmitted over a
        // secure HTTPS connection from the client. When set to TRUE, the
        // cookie will only be set if a secure connection exists.
        'cookie_secure'    => filter_var(
            get_defaulted_env('COOKIE_SECURE', true),
            FILTER_VALIDATE_BOOLEAN
        ),

        // When TRUE the cookie will be made accessible only through the
        // HTTP protocol. This means that the cookie won't be accessible
        // by scripting languages, such as JavaScript.
        'cookie_http_only' => true,

        // Governs the various cache control headers emitted when
        // a session cookie is provided to the client. Value may be one
        // of "nocache", "public", "private", or "private_no_expire";
        // semantics are the same as outlined in
        // http://php.net/session_cache_limiter
        'cache_limiter'    => 'nocache',

        // An integer value indicating when the resource to which the session
        // applies was last modified. If not provided, it uses the last
        // modified time of, in order,
        // - the public/index.php file of the current working directory
        // - the index.php file of the current working directory
        // - the current working directory
        'last_modified'    => null,

        // A boolean value indicating whether or not the session cookie
        // should persist. By default, this is disabled (false); passing
        // a boolean true value will enable the feature. When enabled, the
        // cookie will be generated with an Expires directive equal to the
        // the current time plus the cache_expire value as noted above.
        //
        // As of 1.2.0, developers may define the session TTL by calling the
        // session instance's `persistSessionFor(int $duration)` method. When
        // that method has been called, the engine will use that value even if
        // the below flag is toggled off.
        'persistent'       => false,
    ],
    'analytics'   => [
        'uaid' => get_defaulted_env('GOOGLE_ANALYTICS_ID', ''),
    ],
    'i18n'        => [
        'default_locale' => 'en_GB',
        'default_domain' => 'messages',
        'locale_path'    => '/app/languages/',
    ],
    'csp'         => [
        'enforce'               => filter_var(
            get_defaulted_env('CSP_ENFORCE', true),
            FILTER_VALIDATE_BOOLEAN
        ),
        'report_uri'            => get_defaulted_env('CSP_REPORT_URI', ''),
        'authentication_domain' => get_defaulted_env('CSP_AUTH_DOMAIN', ''),
        'iap_domain'            => get_defaulted_env('CSP_IAP_DOMAIN', ''),
    ],
    'ratelimits'  => [
        'viewer_code_failure' => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => RedisCache::class,
                'options' => [
                    'ttl'           => 60,
                    'server'        => [
                        'host'    => get_defaulted_env('BRUTE_FORCE_CACHE_URL', 'redis'),
                        'port'    => intval(get_defaulted_env('BRUTE_FORCE_CACHE_PORT', 6379)),
                        'timeout' => intval(get_defaulted_env('BRUTE_FORCE_CACHE_TIMEOUT', 60)),
                    ],
                    'persistent_id' => 'brute-force-cache-replication-group',
                    'lib_options'   => [
                        Redis::OPT_SERIALIZER => Redis::SERIALIZER_PHP,
                    ],
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_code_failure'  => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => RedisCache::class,
                'options' => [
                    'ttl'           => 60,
                    'server'        => [
                        'host'    => get_defaulted_env('BRUTE_FORCE_CACHE_URL', 'redis'),
                        'port'    => intval(get_defaulted_env('BRUTE_FORCE_CACHE_PORT', 6379)),
                        'timeout' => intval(get_defaulted_env('BRUTE_FORCE_CACHE_TIMEOUT', 60)),
                    ],
                    'persistent_id' => 'brute-force-cache-replication-group',
                    'lib_options'   => [
                        Redis::OPT_SERIALIZER => Redis::SERIALIZER_PHP,
                    ],
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
        'actor_login_failure' => [
            'type'    => 'keyed',
            'storage' => [
                'adapter' => RedisCache::class,
                'options' => [
                    'ttl'           => 60,
                    'server'        => [
                        'host'    => get_defaulted_env('BRUTE_FORCE_CACHE_URL', 'redis'),
                        'port'    => intval(get_defaulted_env('BRUTE_FORCE_CACHE_PORT', 6379)),
                        'timeout' => intval(get_defaulted_env('BRUTE_FORCE_CACHE_TIMEOUT', 60)),
                    ],
                    'persistent_id' => 'brute-force-cache-replication-group',
                    'lib_options'   => [
                        Redis::OPT_SERIALIZER => Redis::SERIALIZER_PHP,
                    ],
                ],
            ],
            'options' => [
                'interval'              => 60,
                'requests_per_interval' => 4,
            ],
        ],
    ],
];
