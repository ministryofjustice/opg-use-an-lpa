<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;
use Zend\Expressive\Authentication\UserInterface;

/**
 * Class User
 *
 * Implements the UserInterface interface from the Zend Expressive authentication library
 *
 * @package Common\Entity
 */
class User implements UserInterface
{
    /** @var string */
    protected $identity;

    /** @var DateTime */
    protected $lastLogin;

    public function __construct(string $identity, array $roles, array $details)
    {
        $this->identity = $identity;

        $this->lastLogin = array_key_exists('LastLogin', $details) ? $details['LastLogin'] : new DateTime();
    }

    /**
     * Get the unique user identity (id, username, email address or ...)
     */
    public function getIdentity() : string
    {
        return $this->identity;
    }

    /**
     * Get all user roles
     *
     * @return Iterable
     */
    public function getRoles() : iterable
    {
        // Not used.
        return [];
    }

    /**
     * Get a detail $name if present, $default otherwise
     *
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function getDetail(string $name, $default = null)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return $default;
        }
    }

    /**
     * Get all the details, if any
     */
    public function getDetails() : array
    {
        return [
            'lastLogin' => $this->lastLogin
        ];
    }
}