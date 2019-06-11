<?php

declare(strict_types=1);

namespace ViewerTest\Form;

use Common\Form\AbstractCsrfForm;
use PHPUnit\Framework\TestCase;
use Viewer\Form\ShareCode;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Form\Element\Csrf;

class ShareCodeTest extends TestCase
{
    public function testIsAForm()
    {
        $form = new ShareCode($this->setupGuard()->reveal());

        $this->assertInstanceOf(AbstractCsrfForm::class, $form);
        $this->assertInstanceOf(ShareCode::class, $form);
    }

    public function testHasCsrf()
    {
        $form = new ShareCode($this->setupGuard()->reveal());

        $this->assertArrayHasKey("__csrf", $form->getElements());
        $this->assertInstanceOf(Csrf::class, $form->get("__csrf"));
    }

    public function testHasInputSpecification()
    {
        $form = new ShareCode($this->setupGuard()->reveal());

        $this->assertIsArray($form->getInputFilterSpecification());
    }

    protected function setupGuard() : ObjectProphecy
    {
        $guard = $this->prophesize(CsrfGuardInterface::class);
        return $guard;
    }
}