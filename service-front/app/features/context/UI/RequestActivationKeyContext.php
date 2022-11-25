<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ContextUtilities;
use Common\Service\Lpa\OlderLpaApiResponse;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

/**
 * @property mixed  $lpa
 * @property string $userLpaActorToken
 * @property int    $actorId
 * @property string $actorUId
 * @property array  $lpaData
 * @property string $organisation
 * @property string $accessCode
 * @property string $userFirstName
 * @property int    $userId
 * @property string $userSurname
 * @property string $activationCode
 * @property string $codeCreatedDate
 * @psalm-ignore UndefinedThisPropertyFetch
 * @psalm-ignore UndefinedThisPropertyAssignment
 */
class RequestActivationKeyContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    private const ADD_OLDER_LPA_VALIDATE = 'AddOlderLpa::validate';
    private const ADD_OLDER_LPA_CONFIRM  = 'AddOlderLpa::confirm';
    private const CLEANSE_LPA_CLEANSE    = 'CleanseLpa::cleanse';

    /**
     * @var RequestInterface Used to store external requests made to a mocked handler for
     *                       subsequent "Then" step verification.
     */
    private RequestInterface $requestBody;

    /**
     * @Then /^I am taken to the check answers page$/
     */
    public function iAmTakenToTheCheckAnswersPage()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
    }

    /**
     * @Given /^I am told that I have already requested an activation key for this LPA$/
     */
    public function iAmToldThatIHaveAlreadyRequestedAnActivationKeyForThisLPA()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'You\'ve already asked for an activation key for this LPA');
    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     * @Then /^I am told my activation key is being sent$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-activation-key-generation');
    }

    /**
     * @Given /^I have reached the contact details page$/
     */
    public function givenIHaveReachedTheContactDetailsPage()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iHaveProvidedMyCurrentAddress();
        $this->iAmAskedForMyRoleOnTheLPA();
        $this->iConfirmThatIAmThe('Donor');
        $this->iAmAskedForTheAttorneyDetails();
        $this->iProvideTheAttorneyDetails();
    }

    /**
     * @Given I already have a valid activation key for my LPA
     */
    public function iAlreadyHaveAValidActivationKeyForMyLpa()
    {
        $this->activationCode  = 'ACTVATIONCOD';
        $this->codeCreatedDate = (new DateTime())->modify('-15 days')->format('Y-m-d');
    }

    /**
     * @Then /^I am asked for the attorney details$/
     */
    public function iAmAskedForTheAttorneyDetails()
    {
        $this->ui->assertPageAddress('/lpa/add/attorney-details');
        $this->ui->assertPageContainsText('Attorney details');
    }

    /**
     * @When /^I provide the attorney details$/
     */
    public function iProvideTheAttorneyDetails()
    {
        $this->ui->assertPageAddress('/lpa/add/attorney-details');
        $this->ui->fillField('attorney_first_names', $this->lpa->attorneys[0]->firstname);
        $this->ui->fillField('attorney_last_name', $this->lpa->attorneys[0]->surname);
        $attorneyDob = new DateTime($this->lpa->attorneys[0]->dob);
        $this->ui->fillField('attorney_dob[day]', $attorneyDob->format('d'));
        $this->ui->fillField('attorney_dob[month]', $attorneyDob->format('m'));
        $this->ui->fillField('attorney_dob[year]', $attorneyDob->format('Y'));
        $this->ui->pressButton('Continue');
    }

    /**
    /**
     *
     * @Then /^I am asked for my contact details$/
     */
    public function iAmAskedForMyContactDetails()
    {
        $this->ui->assertPageAddress('/lpa/add/contact-details');
        $this->ui->assertPageContainsText('Your contact details');
    }

    /**
     * @Then /^I am asked to check my answers before requesting an activation key$/
     */
    public function iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertPageContainsText('Check your answers');
        $this->ui->assertPageContainsText('700018506654');
        $this->ui->assertPageContainsText('The Attorney Person');
        $this->ui->assertPageContainsText('9 February 1998');
        $this->ui->assertPageContainsText('ABC123');
    }

    /**
     * @Then /^I am asked to check my answers$/
     * @Given /^I am on the check your answers page$/
     */
    public function iAmAskedToCheckMyAnswers()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertPageContainsText('Check your answers');
    }

    /**
     * @When /^I request an activation key for an LPA$/
     */
    public function iRequestAnActivationKey()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Postcode not supplied',
                        'details' => 'Postcode not supplied',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE,
            )
        );

        $this->iPressTheContinueButton();
    }

    /**
     * @When /^I request an activation key for an LPA that already exists in my account$/
     */
    public function iRequestAnActivationKeyThatAlreadyExists()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad request',
                        'details' => 'LPA already added',
                        'data'    =>
                        [
                            'donor'         => [
                                'uId'         => $this->lpa->donor->uId,
                                'firstname'   => $this->lpa->donor->firstname,
                                'middlenames' => $this->lpa->donor->middlenames,
                                'surname'     => $this->lpa->donor->surname,
                            ],
                            'caseSubtype'   => $this->lpa->caseSubtype,
                            'lpaActorToken' => $this->userLpaActorToken,
                        ]
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE,
            )
        );
        $this->iPressTheContinueButton();
    }
    /**
     * @When /^I press the continue button$/
     */
    public function iPressTheContinueButton(): void
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I am asked to provide the donor's details to verify that I am the attorney$/
     */
    public function iAmAskedToProvideTheDonorSDetailsToVerifyThatIAmTheAttorney()
    {
        $this->ui->assertPageAddress('/lpa/add/donor-details');
        $this->ui->assertPageContainsText('The donor\'s details');
    }

    /**
     * @Given /^I am asked for my role on the LPA$/
     */
    public function iAmAskedForMyRoleOnTheLPA()
    {
        $this->ui->assertPageAddress('/lpa/add/actor-role');
        $this->ui->assertPageContainsText('What is your role on the LPA?');
    }

    /**
     * @Given /^I do not provide any selections (.*) on the LPA$/
     */
    public function iDoNotProvideAnySelectionsForMyRoleOnTheLPA($selection)
    {
        if ($selection === 'for my role') {
            $this->ui->assertPageAddress('/lpa/add/actor-role');
            $this->ui->pressButton('Continue');
        } elseif ($selection === 'for current address') {
            $this->ui->assertPageAddress('/lpa/add/actor-address');
            $this->ui->pressButton('Continue');
        }
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'We could not find an LPA to send you an activation key');
    }

    /**
     * @Then /^I am informed that an LPA could not be found with this reference number$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithThisReferenceNumber()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'We could not find an LPA with that reference number');
    }

    /**
     * @Then /^I am not shown a warning that my details must match the information on record$/
     */
    public function iAmNotShownAWarningThatMyDetailsMustMatchTheInformationOnRecord()
    {
        $this->ui->assertPageNotContainsText(
            'These details must match the information we have about you on our records.'
        );
    }

    /**
     * @Given /^I am on the ask for your date of birth page$/
     */
    public function iAmOnTheAskForYourDateOfBirth()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');

        $this->ui->fillField('opg_reference_number', '700018506654');
        $this->ui->pressButton('Continue');

        $this->ui->assertPageAddress('/lpa/request-code/your-name');
        $this->ui->fillField('first_names', 'The Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am on the do you live in the UK page$/
     */
    public function iAmOnTheDoYouLiveInTheUKPage()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');

        $this->ui->fillField('opg_reference_number', '700018506654');
        $this->ui->pressButton('Continue');

        $this->ui->assertPageAddress('/lpa/request-code/your-name');
        $this->ui->fillField('first_names', 'The Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->pressButton('Continue');

        $this->ui->assertPageAddress('/lpa/request-code/date-of-birth');
        $this->ui->fillField('dob[day]', 20);
        $this->ui->fillField('dob[month]', 06);
        $this->ui->fillField('dob[year]', 1995);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am on the ask for your name page$/
     */
    public function iAmOnTheAskForYourNamePage()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');

        $this->ui->fillField('opg_reference_number', '700018506654');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am on the donor details page$/
     */
    public function iAmOnTheDonorDetailsPage()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iHaveProvidedMyCurrentAddress();
        $this->iConfirmThatIAmThe('Attorney');
        $this->ui->assertPageAddress('/lpa/add/donor-details');
    }

    /**
     * @Given /^I am on the request an activation key page$/
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheRequestAnActivationKeyPage()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');
        $this->ui->assertPageAddress('/lpa/request-code/lpa-reference-number');
    }

    /**
     * @Then /^I am redirected to the activation key page$/
     */
    public function iAmRedirectedToTheActivationKeyPage()
    {
        $this->ui->assertPageAddress('/lpa/request-code/lpa-reference-number');
    }

    /**
     * @Then /^I am shown a warning that my details must match the information on record$/
     */
    public function iAmShownAWarningThatMyDetailsMustMatchTheInformationOnRecord()
    {
        $this->ui->assertPageContainsText(
            'These details must match the information we have about you on our records.'
        );
    }

    /**
     * @Then /^I am taken back to the consent and check details page$/
     * @Then /^I am told my activation key request has been received$/
     */
    public function iAmTakenBackToTheConsentAndCheckDetailsPage()
    {
        $this->ui->assertPageAddress('/lpa/add/check-details-and-consent');
    }

    /**
     * @Then /^I am taken back to the date of birth page where I can see my answers and change them$/
     */
    public function iAmTakenBackToTheDateOfBirthPageWhereICanSeeMyAnswersAndChangeThem()
    {
        $this->ui->assertPageAddress('/lpa/request-code/date-of-birth');
        $this->ui->assertFieldContains('dob[day]', '09');
        $this->ui->assertFieldContains('dob[month]', '02');
        $this->ui->assertFieldContains('dob[year]', '1998');
    }

    /**
     * @Then /^I am taken back to the postcode page where I can see my answers and change them$/
     */
    public function iAmTakenBackToThePostcodePageWhereICanSeeMyAnswersAndChangeThem()
    {
        $this->ui->assertPageAddress('/lpa/request-code/postcode');
        $this->ui->assertFieldContains('postcode', 'ABC123');
    }

    /**
     * @Then /^I am taken back to the reference number page where I can see my answer and change it$/
     */
    public function iAmTakenBackToTheReferenceNumberPageWhereICanSeeMyAnswerAndChangeIt()
    {
        $this->ui->assertPageAddress('/lpa/request-code/lpa-reference-number');
        $this->ui->assertFieldContains('opg_reference_number', '700018506654');
    }

    /**
     * @Then /^I am taken back to the your names page where I can see my answers and change them$/
     */
    public function iAmTakenBackToTheYourNamesPageWhereICanSeeMyAnswersAndChangeThem()
    {
        $this->ui->assertPageAddress('/lpa/request-code/your-name');
        $this->ui->assertFieldContains('first_names', 'The Attorney');
        $this->ui->assertFieldContains('last_name', 'Person');
    }

    /**
     * @Then I am told that I cannot request an activation key
     */
    public function iAmToldThatICannotRequestAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'We cannot send an activation key for that LPA');
    }

    /**
     * @Then I am told that I have an activation key for this LPA and where to find it
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLpaAndWhereToFindIt()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'We\'ve already sent you an activation key for this LPA');
    }

    /**
     * @Then /^I am told that I must enter a phone number or select that I cannot take calls$/
     */
    public function iAmToldThatIMustEnterAPhoneNumberOrSelectThatICannotTakeCalls()
    {
        $this->ui->assertPageContainsText(
            'Either enter your phone number or check the box to say you cannot take calls'
        );
    }

    /**
     * @Then /^I am asked to consent and confirm my details$/
     */
    public function iAmAskedToConsentAndConfirmMyDetails()
    {
        $this->ui->assertPageAddress('/lpa/add/check-details-and-consent');
    }

    /**
     * @Given /^I can see my address, attorney role, donor details and telephone number$/
     */
    public function iCanSeeMyAddressAttorneyRoleDonorDetailsAndTelephoneNumber()
    {
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->town);
        $this->ui->assertPageContainsText('Attorney');
        $this->ui->assertPageContainsText($this->lpa->donor->firstname . ' ' . $this->lpa->donor->surname);
        $this->ui->assertPageContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('0123456789');
    }

    /**
     * @Given /^I can see my address, attorney role, donor details and that I have not provided a telephone number$/
     */
    public function iCanSeeMyAddressAttorneyRoleDonorDetailsAndThatIHaveNotProvidedATelephoneNumber()
    {
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->town);
        $this->ui->assertPageContainsText('Attorney');
        $this->ui->assertPageContainsText($this->lpa->donor->firstname . ' ' . $this->lpa->donor->surname);
        $this->ui->assertPageContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('Not provided');
    }

    /**
     * @Given /^I can see my address, attorney role, donor details and address on paper LPA marked unsure$/
     */
    public function iCanSeeMyAddressAttorneyRoleDonorDetailsAndAddressOnPaperLpaAsUnsure()
    {
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->town);
        $this->ui->assertPageContainsText('Attorney');
        $this->ui->assertPageContainsText($this->lpa->donor->firstname . ' ' . $this->lpa->donor->surname);
        $this->ui->assertPageContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('Not sure');
    }

    /**
     * @Then /^I can see the paper address I have input$/
     */
    public function iCanSeeThePaperAddressIHaveInput()
    {
        $this->ui->assertPageContainsText('Unit 18 Peacock Avenue Boggy Bottom Hertfordshire DE65 AAA');
    }

    /**
     * @Given /^I can see my address, donor role, attorney details and telephone number$/
     */
    public function iCanSeeMyAddressDonorRoleAttorneyDetailsAndTelephoneNumber()
    {
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->town);
        $this->ui->assertPageContainsText('Donor');
        $this->ui->assertPageContainsText(
            $this->lpa->attorneys[0]->firstname . ' ' . $this->lpa->attorneys[0]->surname
        );
        $this->ui->assertPageContainsText((new DateTime($this->lpa->attorneys[0]->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('0123456789');
    }

    /**
     * @Then /^I can only see my telephone number$/
     */
    public function iCanOnlySeeMyTelephoneNumber()
    {
        $this->ui->assertPageContainsText('0123456789');
        $this->ui->assertPageNotContainsText('Your role');
        $this->ui->assertPageNotContainsText('Donor\'s name');
        $this->ui->assertPageNotContainsText('Donor\'s date of birth');
    }

    /**
     * @Given /^I can see my address, donor role, attorney details and that I have not provided a telephone number$/
     */
    public function iCanSeeMyAddressDonorRoleAttorneyDetailsAndThatIHaveNotProvidedATelephoneNumber()
    {
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->assertPageContainsText($this->lpa->donor->addresses[0]->town);
        $this->ui->assertPageContainsText(
            $this->lpa->attorneys[0]->firstname . ' ' . $this->lpa->attorneys[0]->surname
        );
        $this->ui->assertPageContainsText((new DateTime($this->lpa->attorneys[0]->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('Donor');
        $this->ui->assertPageContainsText('Not provided');
    }

    /**
     * @Given /^I can see my role is now correctly set as the Attorney$/
     */
    public function iCanSeeMyRoleIsNowCorrectlySetAsTheAttorney()
    {
        $this->ui->assertPageContainsText('Donor');
        $this->ui->assertPageContainsText($this->lpa->donor->firstname . ' ' . $this->lpa->donor->surname);
        $this->ui->assertPageContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('Not provided');
    }

    /**
     * @Given /^I can see my role is now correctly set as the Donor$/
     */
    public function iCanSeeMyRoleIsNowCorrectlySetAsTheDonor()
    {
        $this->ui->assertPageContainsText('Donor');
        $this->ui->assertPageNotContainsText($this->lpa->donor->firstname . ' ' . 'Different');
        $this->ui->assertPageNotContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('0123456789');
    }

    /**
     * @Given /^I can see the donors name is now correct$/
     */
    public function iCanSeeTheDonorsNameIsNowCorrect()
    {
        $this->ui->assertPageContainsText('Attorney');
        $this->ui->assertPageContainsText($this->lpa->donor->firstname . ' ' . 'Different');
        $this->ui->assertPageContainsText((new DateTime($this->lpa->donor->dob))->format('j F Y'));
        $this->ui->assertPageContainsText('0123456789');
    }

    /**
     * @When /^I change the donors name$/
     */
    public function iChangeTheDonorsName()
    {
        $this->ui->assertPageAddress('/lpa/add/donor-details');
        $this->ui->fillField('donor_last_name', 'Different');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I confirm my role on the LPA as an (.*)$/
     */
    public function iConfirmMyRoleOnTheLPAAsAn($role)
    {
        $this->iAmAskedForMyRoleOnTheLPA();
        $this->iConfirmThatIAmThe($role);
    }

    /**
     * @Given /^I confirm that I am the (.*)$/
     */
    public function iConfirmThatIAmThe($role)
    {
        $this->ui->fillField('actor_role_radio', $role);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I confirm details shown to me of the found LPA are correct$/
     */
    public function iConfirmDetailsShownToMeOfTheFoundLPAAreCorrect()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NO_CONTENT,
                json_encode([]),
                self::ADD_OLDER_LPA_CONFIRM
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I confirm that those details are correct$/
     * @When /^I confirm the details I provided are correct$/
     * @Then /^I am taken back to the check answers page$/
     */
    public function iConfirmTheDetailsIProvidedAreCorrect()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertPageContainsText('Check your answers');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I do not see my information$/
     */
    public function iDoNotSeeMyInformation()
    {
        $this->ui->assertPageAddress('/lpa/request-code/your-name');

        $this->ui->assertFieldNotContains('first_names', 'The Attorney');
        $this->ui->assertFieldNotContains('last_name', 'Person');
    }

    /**
     * @Given /^I have provided my current address$/
     */
    public function iHaveProvidedMyCurrentAddress()
    {
        $this->ui->assertPageAddress('/lpa/add/actor-address');
        $this->ui->fillField('actor_address_1', $this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->fillField('actor_address_town', $this->lpa->donor->addresses[0]->town);
        $this->ui->fillField('actor_address_check_radio', 'Yes');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I select (.*) the address is same as on paper LPA$/
     */
    public function iSelectIAmNotSureTheAddressIsSameAsOnPaperLPA($selection)
    {
        if ($selection === 'I am not sure') {
            $this->ui->assertPageAddress('/lpa/add/actor-address');
            $this->ui->fillField('actor_address_1', $this->lpa->donor->addresses[0]->addressLine1);
            $this->ui->fillField('actor_address_town', $this->lpa->donor->addresses[0]->town);
            $this->ui->fillField('actor_address_check_radio', 'Not sure');
            $this->ui->pressButton('Continue');
        }
    }

    /**
     * @Given /^I fill in my (.*) address and I select the address is (.*) as on paper LPA$/
     */
    public function iSelectTheAddressIsNotSameAsOnPaperLPA($ukOrAbroad, $selection)
    {
        $this->ui->assertPageAddress('/lpa/add/actor-address');

        if ($ukOrAbroad === 'UK') {
            $this->ui->fillField('actor_address_1', $this->lpa->donor->addresses[0]->addressLine1);
            $this->ui->fillField('actor_address_town', $this->lpa->donor->addresses[0]->town);
        } elseif ($ukOrAbroad === 'abroad') {
            $this->ui->fillField('actor_abroad_address', $this->lpa->donor->addresses[0]->addressLine1);
        }


        $radioValue = $selection === 'not the same' ? 'No' : 'Yes';
        $this->ui->fillField('actor_address_check_radio', $radioValue);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I select this is not the address same as on paper LPA$/
     * @When /^I click the Continue button$/
     */
    public function iHaveNotGivenTheAddressOnThePaperLPA()
    {
        $this->ui->assertPageAddress('/lpa/add/actor-address');
        $this->ui->fillField('actor_address_1', $this->lpa->donor->addresses[0]->addressLine1);
        $this->ui->fillField('actor_address_town', $this->lpa->donor->addresses[0]->town);
        $this->ui->fillField('actor_address_check_radio', 'No');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I provide details of the donor to verify that I am an attorney$/
     */
    public function iProvideDetailsOfTheDonorToVerifyThatIAmAnAttorney()
    {
        $this->iAmAskedToProvideTheDonorSDetailsToVerifyThatIAmTheAttorney();
        $this->iProvideTheDonorSDetails();
    }

    /**
     * @Then /^I am asked for my full address$/
     * @Then /^I will be navigated back to more details page$/
     */
    public function iWillBeAskedForMyFullAddress()
    {
        $this->ui->assertPageAddress('/lpa/add/actor-address');
        $this->ui->assertPageContainsText('We need some more details');
    }

    /**
     * @Given /^I have given the address on the paper LPA$/
     */
    public function iHaveGivenTheAddressOnThePaperLPA()
    {
        $this->iAmAskedForMyAddressFromThePaperLPA();
        $this->iInputAValidPaperLPAAddress();
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^My current address is recorded in the Sirius task$/
     */
    public function myCurrentAddressIsRecordedInTheSiriusTask()
    {
        Assert::assertStringContainsString(
            sprintf(
                'Current postal address: %s, %s, %s\n',
                $this->lpa->donor->addresses[0]->addressLine1,
                $this->lpa->donor->addresses[0]->town,
                strtoupper($this->lpa->donor->addresses[0]->postcode)
            ),
            $this->base->mockClientHistoryContainer[3]['request']->getBody()->getContents()
        );
    }

    /**
     * @Given /^starts the Add an Older LPA journey$/
     */
    public function startsTheAddAnOlderLPAJourney()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->ui->visit('/lpa/request-code/lpa-reference-number');
        $this->ui->fillField('opg_reference_number', $this->lpa->uId);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^The activation key not been received or was lost$/
     * @Then /^I will receive an email confirming this information$/
     * @Given /^My LPA was registered 'on or after' 1st September 2019$/
     */
    public function theActivationKeyHasBeenReceivedOrWasLost()
    {
        //Not needed for this context
    }

    /**
     * @When /^I enter both a telephone number and select that I cannot take calls$/
     */
    public function iEnterBothATelephoneNumberAndSelectThatICannotTakeCalls()
    {
        $this->ui->fillField('telephone', '0123456789');
        $this->ui->fillField('telephone_option[no_phone]', 'yes');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I enter nothing$/
     */
    public function iEnterNothing()
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given I have been given access to use an LPA via a paper document
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json'));

        $this->activationCode = null;

        $this->userLpaActorToken = '987654321';
        $this->actorId           = 9;
        $this->actorUId          = '700000000054';

        $this->lpaData = [
            'user-lpa-actor-token'       => $this->userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'primary-attorney',
                'details' => [
                    'addresses'    => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => null,
                    'dob'          => '1975-10-05',
                    'email'        => 'string',
                    'firstname'    => 'Ian',
                    'id'           => 0,
                    'middlenames'  => null,
                    'salutation'   => 'Mr',
                    'surname'      => 'Deputy',
                    'systemStatus' => true,
                    'uId'          => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpa'                        => $this->lpa,
        ];
    }

    /**
     * @Given /^I have reached the check details and consent page as the Attorney$/
     */
    public function iHaveReachedTheCheckDetailsAndConsentPageAsTheAttorney()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iHaveProvidedMyCurrentAddress();
        $this->iConfirmThatIAmThe('Attorney');
        $this->iProvideTheDonorsDetails();
        $this->whenIEnterMyTelephoneNumber();
        $this->iAmAskedToConsentAndConfirmMyDetails();
        $this->iCanSeeMyAddressAttorneyRoleDonorDetailsAndTelephoneNumber();
    }

    /**
     * @Given /^I have reached the check details and consent page as the Donor$/
     */
    public function iHaveReachedTheCheckDetailsAndConsentPageAsTheDonor()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iHaveProvidedMyCurrentAddress();
        $this->iConfirmThatIAmThe('Donor');
        $this->iProvideTheAttorneyDetails();
        $this->iSelectThatICannotTakeCalls();
        $this->iAmAskedToConsentAndConfirmMyDetails();
        $this->iCanSeeMyAddressDonorRoleAttorneyDetailsAndThatIHaveNotProvidedATelephoneNumber();
    }

    /**
     * @Given /^I have reached the check details and consent page and said I am unsure of my address on paper LPA$/
     */
    public function iHaveReachedTheCheckDetailsAndConsentPageAsTheAttorneyAndSaidUnsureOfAddressOnPaperLpa()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iSelectIAmNotSureTheAddressIsSameAsOnPaperLPA('I am not sure');
        $this->iConfirmThatIAmThe('Attorney');
        $this->iProvideTheDonorsDetails();
        $this->iSelectThatICannotTakeCalls();
        $this->iAmAskedToConsentAndConfirmMyDetails();
        $this->iCanSeeMyAddressAttorneyRoleDonorDetailsAndAddressOnPaperLpaAsUnsure();
    }

    /**
     * @Given I have requested an activation key with valid details
     * @Given I reach the Check answers part of the Add an Older LPA journey
     */
    public function iHaveRequestedAnActivationKeyWithValidDetails()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iRequestAnActivationKeyWithValidDetails();
        $this->iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey();
    }

    /**
     * @Given I have requested an activation key with valid details and do not live in the UK
     */
    public function iHaveRequestedAnActivationKeyWithValidDetailsAndDoNotLiveInUK()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iRequestAnActivationKeyWithValidDetailsAndDoNotLiveInTheUK();
        $this->iAmAskedToCheckMyAnswers();
    }

    /**
     * @Then /^I press continue and I am taken back to the check answers page$/
     */
    public function iPressContinueAndIAmTakenBackToTheCheckAnswersPage()
    {
        $this->ui->pressButton('Continue');
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
    }

    /**
     * @When I provide details from an LPA registered before Sept 2019
     */
    public function iProvideDetailsFromAnLpaRegisteredBeforeSept2019()
    {
        $this->fillAndSubmitOlderLpaForm();

        // Setup fixture for success response
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'LPA not eligible due to registration date',
                        'details' => 'LPA not eligible due to registration date',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @When I provide details that do not match a valid paper document
     */
    public function iProvideDetailsThatDoNotMatchAValidPaperDocument()
    {
        $this->fillAndSubmitOlderLpaForm();

        // Setup fixture for success response
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'LPA details do not match',
                        'details' => 'LPA details do not match',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @When I provide an LPA number that does not exist
     */
    public function iProvideAnLPANumberThatDoesNotExist()
    {
        $this->fillAndSubmitOlderLpaForm();

        // Setup fixture for success response
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode(
                    [
                        'title'   => 'LPA not found',
                        'details' => 'LPA not found',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @When /^I provide invalid (.*) details of (.*) (.*) (.*)$/
     */
    public function iProvideInvalidDonorDetailsOf($actor, $firstnames, $surname, $dob)
    {
        if ($actor === 'donor') {
            $this->ui->assertPageAddress('/lpa/add/donor-details');
            $this->ui->fillField('donor_first_names', $firstnames);
            $this->ui->fillField('donor_last_name', $surname);

            if (!empty($dob)) {
                $dobParts = explode('-', $dob);
                $this->ui->fillField('donor_dob[day]', $dobParts[0]);
                $this->ui->fillField('donor_dob[month]', $dobParts[1]);
                $this->ui->fillField('donor_dob[year]', $dobParts[2]);
            }
        }
        if ($actor === 'attorney') {
            $this->ui->assertPageAddress('/lpa/add/attorney-details');
            $this->ui->fillField('attorney_first_names', $firstnames);
            $this->ui->fillField('attorney_last_name', $surname);

            if (!empty($dob)) {
                $dobParts = explode('-', $dob);
                $this->ui->fillField('attorney_dob[day]', $dobParts[0]);
                $this->ui->fillField('attorney_dob[month]', $dobParts[1]);
                $this->ui->fillField('attorney_dob[year]', $dobParts[2]);
            }
        }

        $this->ui->pressButton('Continue');
    }

    /**
     * @When I provide the details from a valid paper document
     */
    public function iProvideTheDetailsFromAValidPaperDocument()
    {
        $createdDate = (new DateTime())->modify('-14 days');
        $this->fillAndSubmitOlderLpaForm();

        /**
         * This step definition needs to behave differently dependent on some prior context step
         */
        if ($this->activationCode === null) {
            // Setup fixture for success response
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'donor'       => [
                                'uId'         => $this->lpa->donor->uId,
                                'firstname'   => $this->lpa->donor->firstname,
                                'middlenames' => $this->lpa->donor->middlenames,
                                'surname'     => $this->lpa->donor->surname,
                            ],
                            'lpa-id'      => $this->lpa->uId,
                            'caseSubtype' => $this->lpa->caseSubtype,
                            'role'        => 'donor',
                        ]
                    ),
                    self::ADD_OLDER_LPA_VALIDATE
                )
            );
        } else {
            // Setup fixture for activation key already existing
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    json_encode(
                        [
                            'title'   => 'Bad request',
                            'details' => 'LPA has an activation key already',
                            'data'    => [
                                'donor'                => [
                                    'uId'         => $this->lpa->donor->uId,
                                    'firstname'   => $this->lpa->donor->firstname,
                                    'middlenames' => $this->lpa->donor->middlenames,
                                    'surname'     => $this->lpa->donor->surname,
                                ],
                                'caseSubtype'          => $this->lpa->caseSubtype,
                                'lpaActorToken'        => $this->userLpaActorToken,
                                'activationKeyDueDate' => $createdDate->format('c'),
                            ],
                        ]
                    ),
                    self::ADD_OLDER_LPA_VALIDATE
                )
            );
        }
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already added to my account$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyAddedToMyAccount()
    {
        $this->fillAndSubmitOlderLpaForm();

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad request',
                        'details' => 'LPA already added',
                        'data'    => [
                            'donor'         => [
                                'uId'         => $this->lpa->donor->uId,
                                'firstname'   => $this->lpa->donor->firstname,
                                'middlenames' => $this->lpa->donor->middlenames,
                                'surname'     => $this->lpa->donor->surname,
                            ],
                            'caseSubtype'   => $this->lpa->caseSubtype,
                            'lpaActorToken' => $this->userLpaActorToken,
                        ],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @When /^I provide the donor's details$/
     */
    public function iProvideTheDonorsDetails()
    {
        $this->ui->assertPageAddress('/lpa/add/donor-details');
        $this->ui->fillField('donor_first_names', $this->lpa->donor->firstname);
        $this->ui->fillField('donor_last_name', $this->lpa->donor->surname);
        $donorDob = new DateTime($this->lpa->donor->dob);
        $this->ui->fillField('donor_dob[day]', $donorDob->format('d'));
        $this->ui->fillField('donor_dob[month]', $donorDob->format('m'));
        $this->ui->fillField('donor_dob[year]', $donorDob->format('Y'));
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidDOBFormatOf($day, $month, $year)
    {
        $this->ui->assertPageAddress('/lpa/request-code/date-of-birth');
        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with an invalid live in the UK answer (.*) (.*)$/
     */
    public function iRequestAnActivationKeyWithAnInvalidLiveInTheUKAnswer($liveInUK, $postcode): void
    {
        $this->ui->assertPageAddress('/lpa/request-code/postcode');
        if ($liveInUK !== '') {
            $this->ui->fillField('live_in_uk', $liveInUK);
        }
        $this->ui->fillField('postcode', $postcode);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with an invalid lpa reference number format of "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidLpaReferenceNumberFormatOf($referenceNumber)
    {
        $this->ui->fillField('opg_reference_number', $referenceNumber);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with valid details$/
     */
    public function iRequestAnActivationKeyWithValidDetails()
    {
        $formData = [
            'opg_reference_number' => '700018506654',
            'first_names'          => 'The Attorney',
            'last_name'            => 'Person',
            'live_in_uk'           => 'Yes',
            'postcode'             => 'ABC123',
            'dob[day]'             => '09',
            'dob[month]'           => '02',
            'dob[year]'            => '1998',
        ];

        $this->fillForm($formData);
    }

    /**
     * @When /^I request an activation key with valid details and I do not live in the UK$/
     */
    public function iRequestAnActivationKeyWithValidDetailsAndDoNotLiveInTheUK()
    {
        $formData = [
            'opg_reference_number' => '700018506654',
            'first_names'          => 'The Attorney',
            'last_name'            => 'Person',
            'live_in_uk'           => 'No',
            'dob[day]'             => '09',
            'dob[month]'           => '02',
            'dob[year]'            => '1998',
        ];

        $this->fillForm($formData);
    }

    /**
     * @Then /^I request for a new activation key again$/
     */
    public function iRequestForANewActivationKeyAgain()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NO_CONTENT,
                json_encode([]),
                self::ADD_OLDER_LPA_CONFIRM
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->pressButton('Continue and ask for a new key');
    }

    /**
     * @Given /^I request to change my role$/
     */
    public function iRequestToChangeMyRole()
    {
        $this->ui->clickLink('Change role');
    }

    /**
     * @Given /^I request to change the donors name$/
     */
    public function iRequestToChangeTheDonorsName()
    {
        $this->ui->clickLink('Change donor\'s name');
    }

    /**
     * @Given /^I request to change the address response$/
     */
    public function iRequestToChangeTheAddressResponse()
    {
        $this->ui->clickLink('Change address on paper LPA');
    }

    /**
     * @When /^I request to go back and change my date of birth/
     */
    public function iRequestToGoBackAndChangeMyDateOfBirth()
    {
        $this->ui->clickLink('change-date-of-birth');
    }

    /**
     * @When /^I request to go back and change my LPA reference number/
     */
    public function iRequestToGoBackAndChangeMyLpaReferenceNumber()
    {
        $this->ui->clickLink('change-reference-number');
    }

    /**
     * @When /^I request to go back and change my names/
     */
    public function iRequestToGoBackAndChangeMyNames()
    {
        $this->ui->clickLink('change-name');
    }

    /**
     * @When /^I request to go back and change my postcode/
     */
    public function iRequestToGoBackAndChangeMyPostcode()
    {
        $this->ui->clickLink('change-postcode');
    }

    /**
     * @When /^I select that I cannot take calls$/
     */
    public function iSelectThatICannotTakeCalls()
    {
        $this->ui->assertPageAddress('/lpa/add/contact-details');
        $this->ui->fillField('telephone_option[no_phone]', 'yes');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I should have an option to regenerate an activation key for the old LPA I want to add$/
     */
    public function iShouldHaveAnOptionToRegenerateAnActivationKeyForTheOldLPAIWantToAdd()
    {
        $this->iProvideTheDetailsFromAValidPaperDocument();
        $this->iConfirmTheDetailsIProvidedAreCorrect();
        $this->iAmToldThatIHaveAnActivationKeyForThisLpaAndWhereToFindIt();

        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertPageContainsText('Continue and ask for a new key');
    }

    /**
     * @When /^I visit the Date of Birth page without filling out the form$/
     */
    public function iVisitTheDateOfBirthPageWithoutFillingOutTheForm()
    {
        $this->ui->visit('/lpa/request-code/date-of-birth');
    }

    /**
     * @When /^I visit the Postcode page without filling out the form$/
     */
    public function iVisitThePostcodePageWithoutFillingOutTheForm()
    {
        $this->ui->visit('/lpa/request-code/postcode');
    }

    /**
     * @When /^I visit the Your Name page without filling out the form$/
     */
    public function iVisitTheYourNamePageWithoutFillingOutTheForm()
    {
        $this->ui->visit('/lpa/request-code/your-name');
    }

    /**
     * @Then /^The address given on the paper LPA is recorded in the Sirius task$/
     */
    public function theAddressGivenOnThePaperLPAIsRecordedInTheSiriusTask()
    {
        Assert::assertStringContainsString(
            'Address on LPA: Unit 18, Peacock Avenue, Boggy Bottom, Hertfordshire, DE65 AAA',
            $this->base->mockClientHistoryContainer[3]['request']->getBody()->getContents()
        );
    }

    /**
     * @Then /^It is recorded in the sirius task that the user lives abroad$/
     */
    public function itIsRecordedInTheSiriusTaskThatTheUserLivesAbroad()
    {
        Assert::assertStringContainsString(
            'Requester is not a UK resident',
            $this->base->mockClientHistoryContainer[3]['request']->getBody()->getContents()
        );
    }

    /**
     * @When /^I enter my telephone number$/
     * @Given I provide my telephone number
     */
    public function whenIEnterMyTelephoneNumber()
    {
        $this->ui->assertPageAddress('/lpa/add/contact-details');
        $this->ui->fillField('telephone', '0123456789');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^My LPA has been found but my details did not match$/
     */
    public function myLPAHasBeenFoundButMyDetailsDidNotMatch()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iProvideDetailsThatDoNotMatchAValidPaperDocument();
        $this->iConfirmTheDetailsIProvidedAreCorrect();
    }

    /**
     * @Given /^I am asked for my address from the paper LPA$/
     * @Then /^I will be navigated back to address on paper page$/
     */
    public function iAmAskedForMyAddressFromThePaperLPA()
    {
        $this->ui->visit('/lpa/add/address-on-paper');
    }

    /**
     * @Then /^I am shown an error telling me to input the paper address$/
     */
    public function iAmShownAnErrorTellingMeToInputThePaperAddress()
    {
        $this->ui->assertPageContainsText('Enter your address on the paper LPA');
    }

    /**
     * @Then /^I am shown an error telling me to (.*) on the LPA$/
     */
    public function iAmShownAnErrorTellingMeToMakeEntriesOnTheLPA($selection)
    {
        if ($selection === 'select my role') {
            $this->ui->assertPageContainsText('Select whether you are the donor or an attorney on the LPA');
        } elseif ($selection === 'select if current address') {
            $this->ui->assertPageContainsText(
                'Select whether this is the same address as your address on the paper LPA'
            );
        }
    }

    /**
     * @When /^I input a valid paper LPA address$/
     */
    public function iInputAValidPaperLPAAddress()
    {
        $this->ui->fillField(
            'address_on_paper_area',
            "Unit 18 \n Peacock Avenue \n Boggy Bottom \n Hertfordshire \n DE65 AAA"
        );
        $this->ui->pressButton('Continue');
    }

    protected function fillAndSubmitOlderLpaForm()
    {
        $this->ui->fillField('opg_reference_number', $this->lpa->uId);
        $this->ui->pressButton('Continue');

        $this->ui->fillField(
            'first_names',
            $this->lpa->donor->firstname . ' ' . $this->lpa->donor->middlenames
        );
        $this->ui->fillField('last_name', $this->lpa->donor->surname);
        $this->ui->pressButton('Continue');

        $date = new DateTime($this->lpa->donor->dob);
        $this->ui->fillField('dob[day]', $date->format('d'));
        $this->ui->fillField('dob[month]', $date->format('m'));
        $this->ui->fillField('dob[year]', $date->format('Y'));
        $this->ui->pressButton('Continue');

        $this->ui->fillField('live_in_uk', 'Yes');
        $this->ui->fillField('postcode', $this->lpa->donor->addresses[0]->postcode);
        $this->ui->pressButton('Continue');
    }

    private function fillForm($array)
    {
        $this->ui->assertPageAddress('/lpa/request-code/lpa-reference-number');
        $this->ui->fillField('opg_reference_number', $array['opg_reference_number']);
        $this->ui->pressButton('Continue');

        $this->ui->fillField('first_names', $array['first_names']);
        $this->ui->fillField('last_name', $array['last_name']);
        $this->ui->pressButton('Continue');


        $this->ui->fillField('dob[day]', $array['dob[day]']);
        $this->ui->fillField('dob[month]', $array['dob[month]']);
        $this->ui->fillField('dob[year]', $array['dob[year]']);
        $this->ui->pressButton('Continue');

        $this->ui->fillField('live_in_uk', $array['live_in_uk']);
        if (!empty($array['postcode'])) {
            $this->ui->fillField('postcode', $array['postcode']);
        }
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I am on the Check we've found the right LPA page$/
     * @Given /^I have provided valid details that match the Lpa$/
     */
    public function iAmOnTheCheckLPADetailsPage()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iProvideTheDetailsFromAValidPaperDocument();
        $this->iConfirmTheDetailsIProvidedAreCorrect();
        $this->iAmShownTheDetailsOfAnLPA();
    }

    /**
     * @Then /^I am shown the details of an LPA$/
     * @Then /^I being the donor on the LPA I am not shown the donor name back again$/
     */
    public function iAmShownTheDetailsOfAnLPA()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertPageContainsText('Check we\'ve found the right LPA');
        $this->ui->assertPageNotContainsText('The donor\'s name');
    }

    /**
     * @When /^I realise this is not the correct LPA$/
     */
    public function iRealiseThisIsNotTheCorrectLPA()
    {
        $this->ui->assertPageContainsText('This is not the correct LPA');
        $this->ui->clickLink('This is not the correct LPA');
    }

    /**
     * @Then /^I am taken back to the start of the (.*) process$/
     */
    public function iAmTakenBackToTheStartOfRequestAnActivationKeyProcess()
    {
        $this->ui->assertPageAddress('/lpa/add');
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already requested an activation key for$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad request',
                        'details' => 'Activation key already requested for LPA',
                        'data'    => [
                            'donor'                => [
                                'uId'         => $this->lpa->donor->uId,
                                'firstname'   => $this->lpa->donor->firstname,
                                'middlenames' => $this->lpa->donor->middlenames,
                                'surname'     => $this->lpa->donor->surname,
                            ],
                            'caseSubtype'          => $this->lpa->caseSubtype,
                            'activationKeyDueDate' => '2022-01-30',
                        ],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );

        $this->fillAndSubmitOlderLpaForm();
    }

    /**
     * @When I provide details of an LPA that is not registered
     * @When I provide details of LPA registered after 1st September 2019 where do not match a valid paper document
     */
    public function iProvideDetailsDetailsOfAnLpaThatIsNotRegistered()
    {
        $this->fillAndSubmitOlderLpaForm();
        $this->lpa->status = 'Pending';

        // Setup fixture for success response
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode([]),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @Given /^I have provided details to add an LPA$/
     */
    public function iHaveProvidedDetailsToAddAnLpa()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iProvideTheDetailsFromAValidPaperDocument();
    }

    /**
     * @Given /^My LPA was registered \'([^\']*)\' 1st September 2019 and LPA is \'([^\']*)\' as clean$/
     */
    public function myLPAWasRegistered1stSeptemberAndLPAIsAsClean($regDate, $cleanseStatus)
    {
        if ($cleanseStatus === 'not marked') {
            $this->lpa->lpaIsCleansed = false;
        } else {
            $this->lpa->lpaIsCleansed = true;
        }

        if ($regDate === 'before') {
            $this->lpa->registrationDate = '2019-08-31';
        } else {
            $this->lpa->registrationDate = '2019-09-01';
        }
    }

    /**
     * @Given  /^I have previously requested an activation key$/
     */
    public function iHaveConfirmedTheDetailsOfAnOlderLpaAfterRequestingActivationKeyPreviously()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor();
        $this->iConfirmTheDetailsIProvidedAreCorrect();
        $this->iAmToldThatIHaveAlreadyRequestedAnActivationKeyForThisLPA();
    }

    /**
     * @Then /^I confirm details of the found LPA are correct$/
     * @When /^I request a new activation key$/
     */
    public function iConfirmDetailsOfTheFoundLpaAreCorrect()
    {
        $earliestRegDate = '2019-09-01';

        if (!$this->lpa->lpaIsCleansed && $this->lpa->registrationDate < $earliestRegDate) {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    json_encode(
                        [
                            'title'   => 'Bad request',
                            'details' => 'LPA needs cleansing',
                            'data'    => [
                                'actor_id' => $this->actorUId,
                            ],
                        ]
                    ),
                    self::ADD_OLDER_LPA_CONFIRM
                )
            );

            $this->ui->assertPageAddress('/lpa/request-code/check-answers');
            $this->ui->pressButton('Continue');
        } else {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'data' => [
                                'donor'       => [
                                    'uId'         => $this->lpa->donor->uId,
                                    'firstname'   => $this->lpa->donor->firstname,
                                    'middlenames' => $this->lpa->donor->middlenames,
                                    'surname'     => $this->lpa->donor->surname,
                                ],
                                'caseSubtype' => $this->lpa->caseSubtype,
                                'lpa-id'      => $this->lpa->uId,
                                'role'        => 'donor',
                            ],
                        ]
                    ),
                    self::ADD_OLDER_LPA_CONFIRM
                )
            );

            $this->ui->assertPageAddress('/lpa/request-code/check-answers');
            $this->ui->pressButton('Continue');
        }
    }

    /**
     * @Given  /^I provide my contact details$/
     */
    public function iProvideMyContactDetails()
    {
        $this->iConfirmDetailsOfTheFoundLpaAreCorrect();
        $this->iAmAskedForMyContactDetails();
        $this->whenIEnterMyTelephoneNumber();
    }

    /**
     * @When  /^I confirm that the data is correct and click the confirm and submit button$/
     */
    public function iConfirmThatTheDataIsCorrectAndClickTheConfirmAndSubmitButton()
    {
        $emailTemplate = 'ActivationKeyRequestConfirmationEmailWhenLpaNeedsCleansing';
        $data          = [
            'queuedForCleansing' => true,
        ];

        $this->ui->assertPageContainsText('Check your details');
        $this->ui->assertPageContainsText('Confirm and submit request');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($data),
                self::CLEANSE_LPA_CLEANSE
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));
        $this->ui->assertPageAddress('/lpa/add/check-details-and-consent');
        $this->ui->pressButton('Confirm and submit request');
    }

    /**
     * @Then  /^I should expect it within (.*) time$/
     */
    public function iShouldExpectItWithin($time)
    {
        if ($time === '6 weeks') {
            $date = (new DateTime())->modify('+6 weeks')->format('j F Y');

            $this->ui->assertPageContainsText('We\'ve got your activation key request');
            $this->ui->assertPageContainsText('We\'ll contact you by ' . $date);
        } else {
            $date = (new DateTime())->modify('+2 weeks')->format('j F Y');
            $this->ui->assertPageContainsText('We\'re posting you an activation key');
            $this->ui->assertPageContainsText('You should get the letter by ' . $date);
        }
    }

    /**
     * @Given  /^I provide the additional details asked$/
     */
    public function iProvideTheAdditionalDetailsAsked()
    {
        $this->iConfirmThatIAmThe('Donor');
        $this->iProvideTheAttorneyDetails();
        $this->iSelectThatICannotTakeCalls();
    }

    /**
     * @When /^I do not provide required entries for (.*) (.*) (.*) on the LPA$/
     */
    public function iDoNotProvideRequiredEntriesForAddressPage($address_line_1, $town, $address_as_on_lpa)
    {
        $this->ui->assertPageAddress('/lpa/add/actor-address');
        $this->ui->fillField('actor_address_1', $address_line_1);
        $this->ui->fillField('actor_address_town', $town);
        $this->ui->fillField('actor_address_check_radio', $address_as_on_lpa);

        $this->ui->pressButton('Continue');
    }
}
