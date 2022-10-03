<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestNames;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RequestNamesTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected RequestNames $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key_names';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'      => Csrf::class,
            'first_names' => Text::class,
            'last_name'   => Text::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new RequestNames($guardProphecy->reveal());
    }
}
