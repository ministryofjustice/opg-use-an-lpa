<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\AddressOnPaper;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\LaminasFormTests;
use CommonTest\Form\TestsLaminasForm;
use Laminas\Form\Element\Textarea;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AddressOnPaperTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected AddressOnPaper $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'address_on_paper';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'                => Csrf::class,
            'address_on_paper_area' => Textarea::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form    = new AddressOnPaper($guardProphecy->reveal());
    }
}
