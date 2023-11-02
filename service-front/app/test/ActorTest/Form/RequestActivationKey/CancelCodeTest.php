<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\CancelCode;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Hidden;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CancelCodeTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected CancelCode $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'cancel_code';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'       => Csrf::class,
            'viewer_code'  => Hidden::class,
            'organisation' => Hidden::class,
            'lpa_token'    => Hidden::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new CancelCode($guardProphecy->reveal());
    }
}
