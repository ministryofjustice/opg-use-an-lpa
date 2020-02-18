<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\LpaAdd;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Form\Fieldset\Date;
use CommonTest\Form\{TestsZendForm, ZendFormTests};
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Text;

class LpaAddTest extends TestCase implements TestsZendForm
{
    use ZendFormTests;

    /** @var LpaAdd */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'lpa_add';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'passcode'         => Text::class,
            'reference_number' => Text::class,
            'dob'              => Date::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new LpaAdd($guardProphecy->reveal());
    }
}
