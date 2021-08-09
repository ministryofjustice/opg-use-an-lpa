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

class DonorDetailsTest extends TestCase
{
    use LaminasFormTests;

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
            'first_names'   => Text::class,
            'last_name'     => Text::class,
            'dob'           => Date::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new DonorDetails($guardProphecy->reveal());
    }
}
