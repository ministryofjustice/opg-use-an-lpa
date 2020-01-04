<?php

declare(strict_types=1);

namespace ActorTest\Form;

use Common\Form\AbstractForm;

interface TestsZendForm
{
    public function getFormName(): string;

    public function getFormElements(): array;

    public function getForm(): AbstractForm;
}