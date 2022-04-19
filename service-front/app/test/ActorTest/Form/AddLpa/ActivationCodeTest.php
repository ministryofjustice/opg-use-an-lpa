<?php

declare(strict_types=1);

namespace ActorTest\Form\AddLpa;

use Actor\Form\AddLpa\ActivationCode;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class ActivationCodeTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var ActivationCode */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'lpa_add_activation_code';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'passcode'         => Text::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new ActivationCode($guardProphecy->reveal());
    }
}
