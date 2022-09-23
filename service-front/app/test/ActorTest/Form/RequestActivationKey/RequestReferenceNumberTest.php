<?php

namespace ActorTest\Form\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Form\AbstractForm;
use Common\Form\Element\Csrf;
use Common\Validator\MerisReferenceCheckValidator;
use Laminas\Form\Element\Text;
use CommonTest\Form\{LaminasFormTests, TestsLaminasForm};
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Common\Service\Features\FeatureEnabled;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class RequestReferenceNumberTest extends TestCase implements TestsLaminasForm
{
    use LaminasFormTests;
    use ProphecyTrait;

    protected RequestReferenceNumber $form;

    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    /** @var ObjectProphecy|CsrfGuardInterface */
    private $guardProphecy;


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
            '__csrf'                => Csrf::class,
            'opg_reference_number'  => Text::class
        ];
    }

    public function setUp(): void
    {
        $this->guardProphecy = $this->prophesize(CsrfGuardInterface::class);
        $this->form = new RequestReferenceNumber($this->guardProphecy->reveal(), true);
    }
    /** @test */
    public function it_sets_correct_validator_when_flag_set_to_true(): void
    {
        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key = array_search(
            'Common\Validator\MerisReferenceCheckValidator',
            array_column($validators, 'name')
        );

        $this->assertStringContainsString($validators[$key]['name'], 'Common\Validator\MerisReferenceCheckValidator');
    }

    /** @test */
    public function it_sets_correct_validator_when_flag_set_to_false(): void
    {
        $this->form = new RequestReferenceNumber($this->guardProphecy->reveal(), false);

        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key = array_search('Laminas\Validator\StringLength', array_column($validators, 'name'));

        $this->assertStringContainsString($validators[$key]['name'], 'Laminas\Validator\StringLength');
    }
}
