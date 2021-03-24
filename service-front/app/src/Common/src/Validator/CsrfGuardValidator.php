<?php

namespace Common\Validator;

use InvalidArgumentException;
use Laminas\Validator\Csrf as LaminasCsrf;
use Mezzio\Csrf\CsrfGuardInterface;

/**
 * Simplified CSRF validator that relies on a passed secret
 * Where the secret comes from is beyond the scope of this class
 *
 * @package Common\Validator
 */
class CsrfGuardValidator extends LaminasCsrf
{
    /**
     * Error messages
     * @var array
     */
    protected array $messageTemplates = [
        parent::NOT_SAME => 'As you have not used this service for over 20 minutes, the page has ' .
            'timed out. We\'ve now refreshed the page - please try to sign in again'
    ];

    /**
     * Set to null in order to force the user to manually set it
     *
     * @var null|string
     */
    protected ?string $name = null;

    /**
     * @var CsrfGuardInterface
     */
    protected ?CsrfGuardInterface $guard = null;

    /**
     * Csrf constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        // turns out Zend does some magic initialisation of properties from the options array so
        // we have to check things are valid before __construct'ing the parent.
        if (! (isset($options['guard']) && $options['guard'] instanceof CsrfGuardInterface)) {
            throw new InvalidArgumentException('A CsrfGuardInterface must be supplied to the Csrf Validator');
        }

        parent::__construct($options);
    }

    public function isValid($value, $context = null): bool
    {
        $this->setValue($value);

        if (! $this->getGuard()->validateToken($value)) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }

    public function getGuard(): CsrfGuardInterface
    {
        return $this->guard;
    }

    public function setGuard(CsrfGuardInterface $guard): void
    {
        $this->guard = $guard;
    }

    protected function generateHash(): void
    {
        $token = $this->getGuard()->generateToken();

        $this->setValue($token);
        $this->hash = $token;
    }
}
