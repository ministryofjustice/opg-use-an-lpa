<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\AbstractForm;
use Common\Form\CookieConsent;
use Common\Form\CreateAccount;
use Common\Form\Element\Csrf;
use Laminas\Form\Element\{Hidden, Radio};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class CookieConsentTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var CookieConsent */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'cookieConsent';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'usageCookies'     => Radio::class,
            'referer'         => Hidden::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CookieConsent($guardProphecy->reveal());
    }
}
