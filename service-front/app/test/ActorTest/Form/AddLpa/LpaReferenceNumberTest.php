<?php

declare(strict_types=1);

namespace ActorTest\Form\AddLpa;

use Actor\Form\AddLpa\ActivationKey;
use Actor\Form\AddLpa\LpaReferenceNumber;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LpaReferenceNumberTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;


    /** @var LpaReferenceNumber */
    protected $form;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'lpa_add_reference_number';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'           => Csrf::class,
            'reference_number' => Text::class,
        ];
    }

    public function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new LpaReferenceNumber($guardProphecy->reveal());
    }
}
