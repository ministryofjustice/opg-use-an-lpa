<?php

declare(strict_types=1);

namespace CommonTest\Form;

use Common\Form\AbstractForm;

interface TestsLaminasForm
{
    public function getFormName(): string;

    public function getFormElements(): array;

    public function getForm(): AbstractForm;
}