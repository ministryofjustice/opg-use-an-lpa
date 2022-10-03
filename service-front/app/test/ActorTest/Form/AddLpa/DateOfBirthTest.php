<?php

declare(strict_types=1);

namespace ActorTest\Form\AddLpa;

use Actor\Form\AddLpa\DateOfBirth;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Date;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DateOfBirthTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected DateOfBirth $form;

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
        $this->form    = new DateOfBirth($guardProphecy->reveal());
    }
}
