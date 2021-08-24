<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{TestsLaminasForm, LaminasFormTests};
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;

class CreateNewActivationKeyTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;

    /** @var CreateNewActivationKey */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'create_new_activation_key';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'    => Csrf::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CreateNewActivationKey($guardProphecy->reveal());
    }
}
