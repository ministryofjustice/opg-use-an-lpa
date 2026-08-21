<?php

declare(strict_types=1);

if (!function_exists('get_defaulted_env')) {
    function get_defaulted_env(string $envName, mixed $default = null): mixed
    {
        $var = getenv($envName);
        if ($var === false) {
            return $default;
        }
        return $var;
    }
}
