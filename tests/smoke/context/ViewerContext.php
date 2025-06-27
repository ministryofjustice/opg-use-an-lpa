<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;

class ViewerContext implements Context
{
    use BaseContextTrait;

    public string $shareCode;
    public string $donorSurname;
    public string $organisation;

    #[Given('I have been given access to an LPA via share code')]
    public function iHaveBeenGivenAccessToAnLpaViaShareCode(): void
    {
        $this->shareCode    = 'P9H8A6MLD3AM';
        $this->donorSurname = 'Gilson';
        $this->organisation = 'Test organisation';
    }

    #[Given('I give a valid LPA share code')]
    public function iGiveAValidLpaShareCode(): void
    {
        $this->ui->assertPageAddress('/home');

        if ($this->featureFlags['paper_verification']) {
            $this->ui->assertPageContainsText('Enter the LPA access or paper verification code');
        } else {
            $this->ui->assertPageContainsText('Enter the LPA access code');
        }

        $this->ui->fillField('donor_surname', $this->donorSurname);
        $this->ui->fillField('lpa_code', $this->shareCode);
        $this->ui->pressButton('Continue');
    }

    #[When('/^I enter an organisation name and confirm the LPA is correct$/')]
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        if ($this->featureFlags['paper_verification']) {
            $this->ui->assertPageAddress('/paper-verification/check-code');
        } else {
            $this->ui->assertPageAddress('/check-code');
            $this->ui->assertPageMatchesText('/We’ve found Babara [\w\s\d]*Gilson\'s LPA/');
            $this->ui->assertPageMatchesText('/Babara [\w\s\d]*Gilson/');
            $this->ui->fillField('organisation', $this->organisation);
            $this->ui->pressButton('View this LPA');
        }
    }

    #[Then('I can see the full details of the valid LPA')]
    public function iCanSeeTheFullDetailsOfTheValidLpa(): void
    {
        $this->ui->assertPageAddress('/view-lpa');

        $this->ui->assertPageMatchesText('/Babara [\w\s\d]*Gilson/');
        $this->ui->assertPageContainsText('This health and welfare LPA is valid');
    }

    #[When('I click Download this LPA summary')]
    public function IClickDownloadThisLPASummary(): void
    {
        $this->ui->assertPageAddress('/view-lpa');

        $this->ui->assertPageMatchesText('/Babara [\w\s\d]*Gilson/');
        $this->ui->assertPageContainsText('This health and welfare LPA is valid');
        $this->ui->pressButton('Download this LPA summary');
    }
}
