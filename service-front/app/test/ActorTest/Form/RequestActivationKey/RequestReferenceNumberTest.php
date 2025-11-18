<?php

declare(strict_types=1);

namespace ActorTest\Form\RequestActivationKey;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
        $this->form          = new RequestReferenceNumber($this->guardProphecy->reveal(), true, false);
    }

    #[Test]
    public function it_sets_correct_validator_when_flag_set_to_true(): void
    {
        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key        = array_search(
            MerisReferenceCheckValidator::class,
            array_column($validators, 'name')
        );

        $this->assertStringContainsString($validators[$key]['name'], MerisReferenceCheckValidator::class);
    }

    #[Test]
    public function it_sets_correct_validator_when_flag_set_to_false(): void
    {
        $this->form = new RequestReferenceNumber($this->guardProphecy->reveal(), false, false);

        $validators = $this->getForm()->getInputFilterSpecification()['opg_reference_number']['validators'];
        $key        = array_search(StringLength::class, array_column($validators, 'name'));

        $this->assertStringContainsString($validators[$key]['name'], StringLength::class);
    }

    public static function validationProvider(): array
    {
        return [
            'sirius valid when all disabled'          => [true, '700000000047', false, false],
            'sirius valid when all enabled'           => [true, '700000000047', true, true],
            'sirius invalid when too short'           => [false, '70000000047', false, false],
            'sirius invalid when too long'            => [false, '7000000000047', false, false],
            'sirius invalid with bad check digit'     => [false, '700000000046', false, false],
            'sirius invalid with letters'             => [false, '7000000X0047', false, false],
            'meris (2) valid when enabled'            => [true, '2000000', true, false],
            'meris (3) valid when enabled'            => [true, '3000000', true, false],
            'meris must start with 2 or 3'            => [false, '4000000', true, false],
            'meris must have length of 7'             => [false, '200000', true, false],
            'meris invalid when disabled'             => [false, '2000000', false, false],
            'modernised valid when enabled'           => [true, 'M-1234-5678-9018', false, true],
            'modernised valid lowercase'              => [true, 'm-1234-5678-9018', false, true],
            'modernised invalid when disbled'         => [false, 'M-1234-5678-9018', false, false],
            'modernised invalid when too short'       => [false, 'M-1234-5678-901', false, true],
            'modernised invalid when too long'        => [false, 'M-1234-5678-90181', false, true],
            'modernised invalid with bad check digit' => [false, 'M-1234-5678-9017', false, true],
        ];
    }

    #[Test]
    #[DataProvider('validationProvider')]
    public function it_validates(bool $isValid, string $referenceNumber, bool $meris, bool $paperVerification): void
    {
        $this->guardProphecy->generateToken()->willReturn('abcdef');
        $this->guardProphecy->validateToken('abcdef')->willReturn(true);
        $this->form = new RequestReferenceNumber($this->guardProphecy->reveal(), $meris, $paperVerification);

        $this->form->prepare();
        $this->form->setData([
            '__csrf'               => 'abcdef',
            'opg_reference_number' => $referenceNumber,
        ]);

        $this->assertEquals($isValid, $this->form->isValid());
    }
}
