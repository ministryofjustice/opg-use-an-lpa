<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\Triage;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\{Radio};

class TriageTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var Triage */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'triage';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'triageEntry'     => Radio::class,
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new Triage($guardProphecy->reveal());
    }
}
