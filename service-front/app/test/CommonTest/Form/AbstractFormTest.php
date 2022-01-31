<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\AbstractForm;
use PHPUnit\Framework\TestCase;
use Mezzio\Csrf\CsrfGuardInterface;
use Laminas\Form\Element\Text;

class AbstractFormTest extends TestCase
{
    /**
     * @var AbstractForm
     */
    private $form;

    protected function setUp()
    {
        $guardProphecy = $this->prophesize(CsrfGuardInterface::class);

        $this->form = new class('testConcreteForm', $guardProphecy->reveal()) extends AbstractForm {
            protected array $messageTemplates = ['testKey' => 'testErrorMessage'];

            public function __construct(string $formName, CsrfGuardInterface $csrfGuard)
            {
                parent::__construct($formName, $csrfGuard);

                $this->add(
                    [
                        'name' => 'testElement',
                        'type' => 'Text'
                    ]
                );
            }
        };
    }

    /** @test */
    public function you_can_add_error_messages_to_the_form()
    {
        $this->form->addErrorMessage('testKey');

        $errors = $this->form->getErrorMessages();
        $this->assertContains('testErrorMessage', $errors['']);
    }

    /** @test */
    public function you_can_add_error_messages_to_a_form_element()
    {
        $this->form->addErrorMessage('testKey', 'testElement');

        $errors = $this->form->getElements()['testElement']->getMessages();
        $this->assertContains('testErrorMessage', $errors);
    }

    /** @test */
    public function adding_an_undefined_error_fails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->form->addErrorMessage('doesNotExist');
    }

    /** @test */
    public function adding_an_error_to_a_nonexistent_form_element_fails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->form->addErrorMessage('testKey', 'doesNotExist');
    }

    /** @test */
    public function returns_a_merged_set_of_all_error_messages()
    {
        $this->form->addErrorMessage('testKey');

        /** @var Text $element */
        $element = $this->form->get('testElement');
        $element->setMessages(['testMessage' => 'messageText']);

        $errors = $this->form->getMessages();
        $this->assertContains('testErrorMessage', $errors['']);
        $this->assertContains('messageText', $errors['testElement']);
    }

    /** @test */
    public function returns_error_messages_for_an_element()
    {
        /** @var Text $element */
        $element = $this->form->get('testElement');
        $element->setMessages(['testMessage' => 'messageText']);

        $errors = $this->form->getMessages('testElement');
        $this->assertEquals('messageText', $errors['testMessage']);
    }
}
