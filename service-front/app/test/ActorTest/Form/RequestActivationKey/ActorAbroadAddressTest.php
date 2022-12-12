<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorAbroadAddress;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Textarea;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ActorAbroadAddressTest extends TestCase
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected ActorAbroadAddress $form;

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
            '__csrf'                    => Csrf::class,
            'actor_abroad_address'      => Textarea::class,
            'actor_address_check_radio' => Radio::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new ActorAbroadAddress($guardProphecy->reveal());
    }
}
