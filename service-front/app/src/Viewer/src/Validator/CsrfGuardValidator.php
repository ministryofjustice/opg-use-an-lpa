<?php

namespace Viewer\Validator;

use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Validator\Csrf as ZendCsrf;
use InvalidArgumentException;

/**
 * Simplified CSRF validator that relies on a passed secret
 * Where the secret comes from is beyond the scope of this class
 *
 * @package App\Validator
 */
class CsrfGuardValidator extends ZendCsrf
{
    /**
     * Set to null in order to force the user to manually set it
     *
     * @var null|string
     */
    protected $name = null;

    /**
     * @var CsrfGuardInterface
     */
    protected $guard = null;

    /**
     * Csrf constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if ( ! isset($options['guard']) || ! $options['guard'] instanceof CsrfGuardInterface) {
            throw new InvalidArgumentException('A CsrfGuardInterface must be supplied to the Csrf Validator');
        }

        $this->setGuard($options['guard']);
    }

    public function isValid($value, $context = null) : bool
    {
        $this->setValue($value);

        if ( ! $this->getGuard()->validateToken($value)) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }

    public function getGuard() : CsrfGuardInterface
    {
        return $this->guard;
    }

    public function setGuard(CsrfGuardInterface $guard) : void
    {
        $this->guard = $guard;
    }

    protected function generateHash() : void
    {
        $token = $this->getGuard()->generateToken();

        $this->setValue($token);
        $this->hash = $token;
    }
}
