<?php

declare(strict_types=1);

namespace CommonTest\Form;

use PHPUnit\Framework\Attributes\Test;
use Common\Form\AbstractForm;
use InvalidArgumentException;
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AbstractFormTest extends TestCase
{
    use ProphecyTrait;

    private AbstractForm $form;

    protected function setUp(): void
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);

        /**
         * @var array<string, string> $messageTemplates
         */
        $this->form = new class ('testConcreteForm', $guardProphecy->reveal()) extends AbstractForm {
            protected array $messageTemplates = ['testKey' => 'testErrorMessage'];

            public function __construct(string $formName, CsrfGuardInterface $csrfGuard)
            {
                parent::__construct($formName, $csrfGuard);

                $this->add(
                    [
                        'name' => 'testElement',
                        'type' => 'Text',
                    ]
                );
            }
        };
    }

    #[Test]
    public function you_can_add_error_messages_to_the_form(): void
    {
        $this->form->addErrorMessage('testKey');

        $errors = $this->form->getErrorMessages();
        $this->assertContains('testErrorMessage', $errors['']);
    }

    #[Test]
    public function you_can_add_error_messages_to_a_form_element(): void
    {
        $this->form->addErrorMessage('testKey', 'testElement');

        $errors = $this->form->getElements()['testElement']->getMessages();
        $this->assertContains('testErrorMessage', $errors);
    }

    #[Test]
    public function adding_an_undefined_error_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->form->addErrorMessage('doesNotExist');
    }

    #[Test]
    public function adding_an_error_to_a_nonexistent_form_element_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->form->addErrorMessage('testKey', 'doesNotExist');
    }

    #[Test]
    public function returns_a_merged_set_of_all_error_messages(): void
    {
        $this->form->addErrorMessage('testKey');

        /** @var Text $element */
        $element = $this->form->get('testElement');
        $element->setMessages(['testMessage' => 'messageText']);

        $errors = $this->form->getMessages();
        $this->assertContains('testErrorMessage', $errors['']);
        $this->assertContains('messageText', $errors['testElement']);
    }

    #[Test]
    public function returns_error_messages_for_an_element(): void
    {
        /** @var Text $element */
        $element = $this->form->get('testElement');
        $element->setMessages(['testMessage' => 'messageText']);

        $errors = $this->form->getMessages('testElement');
        $this->assertEquals('messageText', $errors['testMessage']);
    }
}
