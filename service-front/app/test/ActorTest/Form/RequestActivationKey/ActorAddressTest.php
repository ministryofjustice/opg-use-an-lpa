<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorAddress;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ActorAddressTest extends TestCase
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected ActorAddress $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'actor_address';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'               => Csrf::class,
            'actor_address_1'      => Text::class,
            'actor_address_2'      => Text::class,
            'actor_address_town'   => Text::class,
            'actor_address_county' => Text::class,
            'actor_address_check_radio' => Radio::class
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new ActorAddress($guardProphecy->reveal());
    }
}
