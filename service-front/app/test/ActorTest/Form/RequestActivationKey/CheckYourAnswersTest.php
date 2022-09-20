<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckYourAnswers;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class CheckYourAnswersTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected CheckYourAnswers $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'check_answers';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf' => Csrf::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new CheckYourAnswers($guardProphecy->reveal());
    }
}
