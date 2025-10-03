<?php

declare(strict_types=1);

namespace AppTest\DataAccess\Repository\Response;

use App\DataAccess\Repository\Response\CodeView;
use AppTest\LpaUtilities;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CodeViewTest extends TestCase
{
    #[Test]
    public function can_get_data_array_and_time(): void
    {
        $lpa = LpaUtilities::lpaStoreLpaFixture();

        $codeView = new CodeView($lpa);

        $this->assertEquals($lpa, $codeView->getData());
    }
}
