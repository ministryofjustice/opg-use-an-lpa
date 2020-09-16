<?php

declare(strict_types=1);

return [

    'application' => getenv('CONTEXT') ?: null,

    'version' => getenv('CONTAINER_VERSION') ?: 'dev',

    'api' => [
        'uri' => getenv('API_SERVICE_URL') ?: null,
    ],

    'pdf' => [
        'uri' => getenv('PDF_SERVICE_URL') ?: null,
    ],

    'aws' => [
        'region'    => 'eu-west-1',
        'version'   => 'latest',

        'Kms' => [
            'endpoint' => getenv('AWS_ENDPOINT_KMS') ?: null,
        ],
    ],

    'notify' => [
        'api' => [
            'key' => getenv('NOTIFY_API_KEY') ?: null,
        ],
    ],

    'session' => [

        // Time in seconds after which a session will expire.
        'expires' => 60 * getenv('SESSION_EXPIRES') ?: 1200,             // default to 20 minutes

        'cookie_ttl' => 60 * getenv('SESSION_COOKIE_LIFETIME') ?: 86400, // default to one day

        'key' => [
            // KMS alias to use for data key generation.
            'alias' => getenv('KMS_SESSION_CMK_ALIAS') ?: null,
        ],

        // The name of the session cookie. This name must comply with
        // the syntax outlined in https://tools.ietf.org/html/rfc6265.html
        'cookie_name' => 'session',

        // The (sub)domain that the cookie is available to. Setting this
        // to a subdomain (such as 'www.example.com') will make the cookie
        // available to that subdomain and all other sub-domains of it
        // (i.e. w2.www.example.com). To make the cookie available to the
        // whole domain (including all subdomains of it), simply set the
        // value to the domain name ('example.com', in this case).
        // Leave this null to use browser default (current hostname).
        'cookie_domain' => null,

        // The path prefix of the cookie domain to which it applies.
        'cookie_path' => '/',

        // Indicates that the cookie should only be transmitted over a
        // secure HTTPS connection from the client. When set to TRUE, the
        // cookie will only be set if a secure connection exists.
        'cookie_secure' => getenv('COOKIE_SECURE') === 'false' ? false : true,

        // When TRUE the cookie will be made accessible only through the
        // HTTP protocol. This means that the cookie won't be accessible
        // by scripting languages, such as JavaScript.
        'cookie_http_only' => true,

        // Governs the various cache control headers emitted when
        // a session cookie is provided to the client. Value may be one
        // of "nocache", "public", "private", or "private_no_expire";
        // semantics are the same as outlined in
        // http://php.net/session_cache_limiter
        'cache_limiter' => 'nocache',

        // An integer value indicating when the resource to which the session
        // applies was last modified. If not provided, it uses the last
        // modified time of, in order,
        // - the public/index.php file of the current working directory
        // - the index.php file of the current working directory
        // - the current working directory
        'last_modified' => null,

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
        'persistent' => false,
    ],

    'analytics' => [
        'uaid' => getenv('GOOGLE_ANALYTICS_ID') ?: "",
    ],

    'authentication' => [
        'username' => 'email',
        'redirect' => '/login',
    ],

    'i18n' => [
        'default_locale' => 'en_GB',
        'default_domain' => 'messages',
        'locale_path' => '/app/languages/'
    ],

    'ratelimits' => [
        'viewer_code_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'redis',
                    'options' => [
                        'ttl' => 60,
                        'server' => [
                            'persistent_id' => 'brute-force-cache-replication-group',
                            'host' => getenv('BRUTE_FORCE_CACHE_URL') ?: 'redis',
                            'port' => getenv('BRUTE_FORCE_CACHE_PORT') ?: 6379,
                            'timeout' => getenv('BRUTE_FORCE_CACHE_TIMEOUT') ?: 60
                        ],
                        'lib_options' => [
                            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP
                        ]
                    ],
                ],
            ],
            'options' => [
                'interval' => 60,
                'requests_per_interval' => 4
            ]
        ],
        'actor_code_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'redis',
                    'options' => [
                        'ttl' => 60,
                        'server' => [
                            'persistent_id' => 'brute-force-cache-replication-group',
                            'host' => getenv('BRUTE_FORCE_CACHE_URL') ?: 'redis',
                            'port' => getenv('BRUTE_FORCE_CACHE_PORT') ?: 6379,
                            'timeout' => getenv('BRUTE_FORCE_CACHE_TIMEOUT') ?: 60
                        ],
                        'lib_options' => [
                            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP
                        ]
                    ],
                ],
            ],
            'options' => [
                'interval' => 60,
                'requests_per_interval' => 4
            ]
        ],
        'actor_login_failure' => [
            'type' => 'keyed',
            'storage' => [
                'adapter' => [
                    'name'    => 'redis',
                    'options' => [
                        'ttl' => 60,
                        'server' => [
                            'persistent_id' => 'brute-force-cache-replication-group',
                            'host' => getenv('BRUTE_FORCE_CACHE_URL') ?: 'redis',
                            'port' => getenv('BRUTE_FORCE_CACHE_PORT') ?: 6379,
                            'timeout' => getenv('BRUTE_FORCE_CACHE_TIMEOUT') ?: 60
                        ],
                        'lib_options' => [
                            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP
                        ]
                    ],
                ],
            ],
            'options' => [
                'interval' => 60,
                'requests_per_interval' => 4
            ]
        ]
    ]

];
