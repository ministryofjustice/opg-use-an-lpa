<?php

declare(strict_types=1);

namespace ViewerTest\Form;

use Viewer\Form\VerificationCode;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Laminas\Form\Element\Radio;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class VerificationCodeSentToTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected VerificationCode $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'verification_code';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'     => Csrf::class,
            'attorney_name'   => Text::class,
            'verification_code_receiver' => Radio::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new VerificationCode($guardProphecy->reveal());
    }
}
