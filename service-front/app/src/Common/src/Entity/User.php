<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use JsonSerializable;
use Mezzio\Authentication\UserInterface;
use RuntimeException;

/**
 * Implements the UserInterface interface from the Zend Expressive authentication library
 */
class User implements UserInterface, JsonSerializable
{
    protected string $email;

    protected bool $needsReset;
    protected ?DateTimeInterface $lastLogin;
    protected ?string $subject;
    protected ?string $idToken;

    public function __construct(protected string $identity, array $roles, array $details)
    {
        $this->lastLogin = null;

        if (empty($details['Email'])) {
            throw new RuntimeException('Expected database value "Email" not returned');
        }

        $this->email      = $details['Email'];
        $this->needsReset = !empty($details['NeedsReset']);
        $this->subject    = $details['Subject'] ?? null;
        $this->idToken    = $details['IdToken'] ?? null;

        if (!empty($details['LastLogin'])) {
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
     * @param null|mixed $default
     * @return mixed
     */
    public function getDetail(string $name, $default = null): mixed
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
     *
     * @return array{
     *     Email: string,
     *     LastLogin: ?array,
     *     NeedsReset: bool,
     *     Subject?: string,
     *     IdToken?: string,
     * }
     */
    public function getDetails(): array
    {
        $array = [
            'Email'      => $this->email,
            'LastLogin'  => $this->lastLogin,
            'NeedsReset' => $this->needsReset,
        ];

        if ($this->subject !== null) {
            $array['Subject'] = $this->subject;
        }
        if ($this->idToken !== null) {
            $array['IdToken'] = $this->idToken;
        }

        return $array;
    }

    /**
     * Attempts to figure out how to construct a valid DateTime from the information made available.
     *
     * @param array|string $date An array, or string containing a serialised DateTime or ATOM compliant date.
     * @throws Exception
     */
    public function setLastLogin(array|string $date): void
    {
        // if this is being called via a construction from the database it will be an ISO/ATOM
        // format string.
        if (is_string($date)) {
            $this->lastLogin = new DateTimeImmutable($date);
        }

        // if this is being called via a reconstruction from the session the DateTime object
        // will have been deconstructed to a key/value array. So build a new DateTime from that.
        if (is_array($date) && array_key_exists('date', $date)) {
            $this->lastLogin = new DateTimeImmutable($date['date'], new DateTimeZone($date['timezone']));
        }
    }

    /**
     * @return array{
     *      Email: string,
     *      LastLogin: ?string,
     *      NeedsReset: bool,
     *      Subject?: string,
     *      IdToken?: string,
     *  }
     */
    public function jsonSerialize(): array
    {
        $details = $this->getDetails();

        // Encode the last login time as a string
        if ($this->lastLogin !== null) {
            $details['LastLogin'] = $this->lastLogin->format(DateTimeInterface::ATOM);
        }

        return $details;
    }
}
