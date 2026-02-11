<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\AbstractForm;
use Laminas\Form\Element\Text;
use Mezzio\Csrf\CsrfGuardInterface;
use PHPUnit\Framework\Attributes\Test;
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
            /** @var string[] */
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
    public function returns_error_messages_for_an_element(): void
    {
        /** @var Text $element */
        $element = $this->form->get('testElement');
        $element->setMessages(['testMessage' => 'messageText']);

        $errors = $this->form->getMessages('testElement');
        $this->assertEquals('messageText', $errors['testMessage']);
    }
}
