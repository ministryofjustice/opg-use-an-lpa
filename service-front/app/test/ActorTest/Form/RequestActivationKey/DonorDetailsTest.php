<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\DonorDetails;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use CommonTest\Form\LaminasFormTests;
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DonorDetailsTest extends TestCase
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected DonorDetails $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'donor_details';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'        => Csrf::class,
            'donor_first_names'   => Text::class,
            'donor_last_name'     => Text::class,
            'donor_dob'           => Date::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new DonorDetails($guardProphecy->reveal());
    }
}
