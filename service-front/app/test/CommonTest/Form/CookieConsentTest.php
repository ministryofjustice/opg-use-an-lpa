<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\CookieConsent;
use Common\Form\CreateAccount;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\{Radio, Hidden};

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

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CookieConsent($guardProphecy->reveal());
    }
}
