<?php

declare(strict_types=1);

namespace ActorTest\Form\AddLpa;

use Actor\Form\AddLpa\ActivationCode;
use Actor\Form\AddLpa\DateOfBirth;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Laminas\Form\Element\Date;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

class DateOfBirthTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var DateOfBirth */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'lpa_add_dob';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class,
            'dob'    => Date::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new DateOfBirth($guardProphecy->reveal());
    }
}
