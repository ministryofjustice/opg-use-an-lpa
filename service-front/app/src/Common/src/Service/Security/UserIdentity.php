<?php

declare(strict_types=1);

namespace Common\Service\Security;

use function hash;

final class UserIdentity
{
    public string $data;

    public function __construct(
        string $accept,
        string $acceptEncoding,
        string $acceptLanguage,
        string $userAgent,
        string $ip,
    ) {
        $this->data = sprintf(
            'accept:%s,encoding:%s,lang:%s,agent:%s,ip:%s',
            $accept,
            $acceptEncoding,
            $acceptLanguage,
            $userAgent,
            $ip // simple hash is all that's needed
        );
    }

    public function hash(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        return hash('sha256', $this->data);
    }
}
