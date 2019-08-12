<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;
use DateTimeZone;
use Exception;
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
        $this->lastLogin = null;

        if (array_key_exists('LastLogin', $details)) {
            $this->setLastLogin($details['LastLogin']);
        }
    }

    /**
     * Get the unique user identity (id, username, email address or ...)
     */
    public function getIdentity(): string
    {
        return $this->identity;
    }

    /**
     * Get all user roles
     *
     * @return Iterable
     */
    public function getRoles(): iterable
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
        $propertyName = lcfirst($name);

        if (property_exists($this, $propertyName)) {
            return $this->$propertyName ?? $default;
        } else {
            return $default;
        }
    }

    /**
     * Get all the details, if any
     */
    public function getDetails(): array
    {
        return [
            'LastLogin' => $this->lastLogin
        ];
    }

    /**
     * Attempts to figure out how to construct a valid DateTime from the information made available.
     *
     * @param mixed $date An array, or string containing a serialised DateTime or ATOM compliant date.
     * @throws Exception
     */
    public function setLastLogin($date): void
    {
        // if this is being called via a construction from the database it will be an ISO/ATOM
        // format string.
        if (is_string($date)) {
            $this->lastLogin = new DateTime($date);
        }

        // if this is being called via a reconstruction from the session the the DateTime object
        // will have been deconstructed to a key/value array. So build a new DateTime from that.
        if (is_array($date) && array_key_exists('date', $date)) {
            $this->lastLogin = new DateTime($date['date'], new DateTimeZone($date['timezone']));
        }
    }
}