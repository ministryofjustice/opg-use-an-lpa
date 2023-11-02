<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Service\Features\FeatureEnabled;
use Common\Validator\MerisReferenceCheckValidator;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Laminas\Form\Element\Text;
use Laminas\Validator\StringLength;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class RequestReferenceNumberTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected RequestReferenceNumber $form;

    private ObjectProphecy|FeatureEnabled $featureEnabledProphecy;
    private ObjectProphecy|CsrfGuardInterface $guardProphecy;

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function getFormName(): string
    {
        return 'request_activation_key_reference_number';
    }

    public function getFormElements(): array
    {
        return [
            '__csrf'               => Csrf::class,
            'opg_reference_number' => Text::class,
        ];
    }

    public function setUp(): void
    {
        $this->guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form          = new RequestReferenceNumber($this->guardProphecy->reveal(), true);
    }

    /**
     * @test
     */
    public function it_sets_correct_validator_when_flag_set_to_true(): void
    {
        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key        = array_search(
            MerisReferenceCheckValidator::class,
            array_column($validators, 'name')
        );

        $this->assertStringContainsString($validators[$key]['name'], MerisReferenceCheckValidator::class);
    }

    /**
     * @test
     */
    public function it_sets_correct_validator_when_flag_set_to_false(): void
    {
        $this->form = new RequestReferenceNumber($this->guardProphecy->reveal(), false);

        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key        = array_search(StringLength::class, array_column($validators, 'name'));

        $this->assertStringContainsString($validators[$key]['name'], StringLength::class);
    }
}
