<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Hidden;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;

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
            '__csrf'           => Csrf::class,
            'force_activation' => Hidden::class
        ];
    }

    public function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new CreateNewActivationKey($guardProphecy->reveal());
    }

    /** @test */
    public function it_defaults_to_false()
    {
        $this->assertEquals('no', $this->form->get('force_activation')->getValue());
    }
}
