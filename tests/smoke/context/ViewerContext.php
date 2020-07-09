<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;

/**
 * Class ViewerContext
 *
 * @package BehatTest\Context
 *
 * @property string $shareCode
 * @property string $donorSurname
 */
class ViewerContext implements Context
{
    use BaseContextTrait;

    /**
     * @Given I have been given access to an LPA via share code
     */
    public function iHaveBeenGivenAccessToAnLpaViaShareCode(): void
    {
        $this->shareCode = 'P9H8A6MLD3AM';
        $this->donorSurname = 'Gilson';
    }

    /**
     * @Given I give a valid LPA share code
     */
    public function iGiveAValidLpaShareCode(): void
    {
        $this->ui->assertHomepage();

        $this->ui->clickLink('Start');

        $this->ui->assertPageContainsText('Enter the LPA access code');

        $this->ui->fillField('donor_surname', $this->donorSurname);
        $this->ui->fillField('lpa_code', $this->shareCode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When I confirm the LPA is correct
     */
    public function iConfirmTheLpaIsCorrect(): void
    {
        $this->ui->assertPageAddress('/check-code');

        $this->ui->assertPageContainsText('We’ve found this LPA');
        $this->ui->assertPageContainsText('Babara Gilson');

        $this->ui->clickLink('View this LPA');
    }

    /**
     * @Then I can see the full details of the valid LPA
     */
    public function iCanSeeTheFullDetailsOfTheValidLpa(): void
    {
        $this->ui->assertPageAddress('/view-lpa');

        $this->ui->assertPageContainsText('Babara Gilson');
        $this->ui->assertPageContainsText('This health and welfare LPA is valid');
    }
}
