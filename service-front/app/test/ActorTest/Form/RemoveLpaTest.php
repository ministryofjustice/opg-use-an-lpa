<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Actor\Form\RemoveLpa;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Laminas\Form\Element\Hidden;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RemoveLpaTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected RemoveLpa $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'remove_lpa';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'          => Csrf::class,
            'actor_lpa_token' => Hidden::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new RemoveLpa($guardProphecy->reveal());
    }
}
