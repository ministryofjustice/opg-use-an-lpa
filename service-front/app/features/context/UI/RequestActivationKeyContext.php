<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Actor\Validator\OptionSelectedValidator;
use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\Features\FeatureEnabled;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * @property mixed  $lpa
 * @property string $userLpaActorToken
 * @property int    $actorId
 * @property array  $lpaData
 * @property string $organisation
 * @property string $accessCode
 * @property string $userFirstName
 * @property int    $userId
 * @property string $userSurname
 * @property string $activationCode
 * @property string $codeCreatedDate
 */
class RequestActivationKeyContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /**
     * @Then /^a letter is requested containing a one time use code$/
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
        $this->ui->visit('/lpa/add/contact-details');
    }

    /**
     * @Given I already have a valid activation key for my LPA
     */
    public function iAlreadyHaveAValidActivationKeyForMyLpa()
    {
        $this->activationCode = 'ACTVATIONCOD';
        $this->codeCreatedDate = (new DateTime())->modify('-15 days')->format('Y-m-d');
    }

    /**
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
        $this->ui->assertPageContainsText('700000000001');
        $this->ui->assertPageContainsText('The Attorney Person');
        $this->ui->assertPageContainsText('9 February 1998');
        $this->ui->assertPageContainsText('ABC123');
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
        $this->ui->assertPageContainsText('What is your role on the LPA?');
    }

    /**
     * @Then I am informed that an LPA could not be found with these details
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->assertElementContainsText('h1', 'We could not find an LPA with the details you entered');
    }

    /**
     * @Given /^I am on the ask for your date of birth page$/
     */
    public function iAmOnTheAskForYourDateOfBirth()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');

        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->pressButton('Continue');

        $this->ui->assertPageAddress('/lpa/request-code/your-name');
        $this->ui->fillField('first_names', 'The Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am on the ask for your name page$/
     */
    public function iAmOnTheAskForYourNamePage()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');

        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am on the donor details page$/
     */
    public function iAmOnTheDonorDetailsPage()
    {
        $this->myLPAHasBeenFoundButMyDetailsDidNotMatch();
        $this->iConfirmThatIAmTheAttorney();
        $this->ui->assertPageAddress('/lpa/add/donor-details');
    }

    /**
     * @Given /^I am on the request an activation key page$/
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheRequestAnActivationKeyPage()
    {
        $this->ui->visit('/lpa/request-code/lpa-reference-number');
        $this->ui->assertPageAddress('lpa/request-code/lpa-reference-number');
    }

    /**
     * @Then /^I am redirected to the activation key page$/
     */
    public function iAmRedirectedToTheActivationKeyPage()
    {
        $this->ui->assertPageAddress('lpa/request-code/lpa-reference-number');
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
        $this->ui->assertFieldContains('opg_reference_number', '700000000001');
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
     * @Then /^I am told that I must enter a phone number$/
     */
    public function iAmToldThatIMustEnterAPhoneNumber()
    {
        $this->ui->assertPageContainsText(OptionSelectedValidator::OPTION_MUST_BE_SELECTED_MESSAGE);
    }

    /**
     * @When /^I confirm that I am the attorney$/
     */
    public function iConfirmThatIAmTheAttorney()
    {
        $this->ui->fillField('actor_role_radio', 'Attorney');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I confirm that I am the donor on the LPA$/
     */
    public function iConfirmThatIAmTheDonorOnTheLPA()
    {
        $this->ui->fillField('actor_role_radio', 'Donor');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I confirm details shown to me of the found LPA are correct$/
     */
    public function iConfirmDetailsShownToMeOfTheFoundLPAAreCorrect()
    {
        $this->apiFixtures->patch('/v1/older-lpa/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NO_CONTENT,
                    [],
                    json_encode([])
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('personalisation', $params);
                }
            );

        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I confirm that those details are correct$/
     * @When /^I confirm the details I provided are correct$/
     */
    public function iConfirmTheDetailsIProvidedAreCorrect()
    {
        $this->ui->assertPageAddress('/lpa/request-code/check-answers');
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

        $this->userLpaActorToken = '987654321';
        $this->actorId = 9;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date' => 'today',
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'addresses' => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country' => '',
                            'county' => '',
                            'id' => 0,
                            'postcode' => '',
                            'town' => '',
                            'type' => 'Primary',
                        ],
                    ],
                    'companyName' => null,
                    'dob' => '1975-10-05',
                    'email' => 'string',
                    'firstname' => 'Ian',
                    'id' => 0,
                    'middlenames' => null,
                    'salutation' => 'Mr',
                    'surname' => 'Deputy',
                    'systemStatus' => true,
                    'uId' => '700000000054',
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpa' => $this->lpa,
        ];
    }

    /**
     * @Given /^I have requested an activation key with valid details$/
     */
    public function iHaveRequestedAnActivationKeyWithValidDetails()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iRequestAnActivationKeyWithValidDetails();
        $this->iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey();
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
        $this->apiFixtures->post('/v1/older-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'LPA not eligible due to registration date',
                            'details' => 'LPA not eligible due to registration date',
                            'data' => [],
                        ]
                    )
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
        $this->apiFixtures->post('/v1/older-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'LPA details do not match',
                            'details' => 'LPA details do not match',
                            'data' => [],
                        ]
                    )
                )
            );
    }

    /**
     * @When I provide the details from a valid paper document
     */
    public function iProvideTheDetailsFromAValidPaperDocument()
    {
        $this->fillAndSubmitOlderLpaForm();

        /**
         * This step definition needs to behave differently dependant on some prior context step
         */
        if ($this->activationCode === null) {
            // Setup fixture for success response
            $this->apiFixtures->post('/v1/older-lpa/validate')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'donor' => [
                                    'uId' => $this->lpa->donor->uId,
                                    'firstname' => $this->lpa->donor->firstname,
                                    'middlenames' => $this->lpa->donor->middlenames,
                                    'surname' => $this->lpa->donor->surname,
                                ],
                                'caseSubtype' => $this->lpa->caseSubtype,
                            ]
                        )
                    )
                );
        } else {
            // Setup fixture for activation key already existing
            $this->apiFixtures->post('/v1/older-lpa/validate')
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_BAD_REQUEST,
                        [],
                        json_encode(
                            [
                                'title' => 'Bad request',
                                'details' => 'LPA has an activation key already',
                                'data' => [
                                    'donor'         => [
                                        'uId'           => $this->lpa->donor->uId,
                                        'firstname'     => $this->lpa->donor->firstname,
                                        'middlenames'   => $this->lpa->donor->middlenames,
                                        'surname'       => $this->lpa->donor->surname,
                                    ],
                                    'caseSubtype' => $this->lpa->caseSubtype,
                                    'lpaActorToken' => $this->userLpaActorToken
                                ],
                            ]
                        )
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

        $this->apiFixtures->post('/v1/older-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'Bad request',
                            'details' => 'LPA already added',
                            'data' => [
                                'donor'         => [
                                    'uId'           => $this->lpa->donor->uId,
                                    'firstname'     => $this->lpa->donor->firstname,
                                    'middlenames'   => $this->lpa->donor->middlenames,
                                    'surname'       => $this->lpa->donor->surname,
                                ],
                                'caseSubtype' => $this->lpa->caseSubtype,
                                'lpaActorToken' => $this->userLpaActorToken
                            ],
                        ]
                    )
                )
            );
    }

    /**
     * @When /^I provide the donor's details$/
     */
    public function iProvideTheDonorSDetails()
    {
        $this->ui->fillField('donor_first_names', $this->lpa->donor->firstname);
        $this->ui->fillField('donor_last_name', $this->lpa->donor->surname);
        $this->ui->fillField('donor_dob[day]', '09');
        $this->ui->fillField('donor_dob[month]', '02');
        $this->ui->fillField('donor_dob[year]', '1998');
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
            'opg_reference_number' => '700000000001',
            'first_names' => 'The Attorney',
            'last_name' => 'Person',
            'postcode' => 'ABC123',
            'dob[day]' => '09',
            'dob[month]' => '02',
            'dob[year]' => '1998',
        ];

        $this->fillForm($formData);
    }

    /**
     * @Then /^I request for a new activation key again$/
     */
    public function iRequestForANewActivationKeyAgain()
    {
        $this->apiFixtures->patch('/v1/older-lpa/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NO_CONTENT,
                    [],
                    json_encode([])
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('personalisation', $params);
                }
            );

        $this->ui->pressButton('Continue and ask for a new key');
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
     * @When /^I visit the Date of Birth page without filling out the form$/
     */
    public function iVisitTheDateOfBirthPageWithoutFillingOutTheForm()
    {
        $this->ui->visit('lpa/request-code/date-of-birth');
    }

    /**
     * @When /^I visit the Postcode page without filling out the form$/
     */
    public function iVisitThePostcodePageWithoutFillingOutTheForm()
    {
        $this->ui->visit('lpa/request-code/postcode');
    }

    /**
     * @When /^I visit the Your Name page without filling out the form$/
     */
    public function iVisitTheYourNamePageWithoutFillingOutTheForm()
    {
        $this->ui->visit('lpa/request-code/your-name');
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

        $this->ui->fillField('postcode', ($this->lpa->donor->addresses[0])->postcode);
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

        $this->ui->fillField('postcode', $array['postcode']);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I am on the check LPA details page$/
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
}
