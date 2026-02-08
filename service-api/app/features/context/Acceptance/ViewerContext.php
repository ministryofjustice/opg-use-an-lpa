<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Command;
use Aws\Result;
use Aws\ResultInterface;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use DateTime;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use stdClass;

class ViewerContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    private string $attorneyName;
    private string $dateOfBirth;
    private ?int $noOfAttorneys;
    private bool $sentToDonor;
    private string $viewerCode;
    private string $donorSurname;
    private stdClass $lpa;
    private string $viewerCodeOrganisation;
    private string $lpaViewedBy;

    #[Then('I am told that I cannot view the LPA summary')]
    public function iAmToldThatICannotViewTheLPASummary(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    #[Then('I am told that the LPA has been found')]
    public function iAmToldThatTheLPAHasBeenFound(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('donorName', $lpaData);
        Assert::assertArrayHasKey('type', $lpaData);
        Assert::assertArrayHasKey('status', $lpaData);
        Assert::assertEquals('lpastore', $lpaData['source']);
    }

    #[When('I ask to verify my information')]
    public function iAskToVerifyMyInformation(): void
    {
        $codeData = match ($this->viewerCode) {
            'P-1234-1234-1234-12' => [
                'lpa'   => 'M-7890-0400-4000',
                'actor' => '',
            ],
            'P-5678-5678-5678-56' => [
                'lpa'           => 'M-7890-0400-4000',
                'actor'         => '',
                'expiry_date'   => '2024-12-13',
                'expiry_reason' => 'first_time_use',
            ],
            'P-3456-3456-3456-34' => [
                'lpa'           => 'M-7890-0400-4000',
                'actor'         => '',
                'expiry_date'   => '2025-10-24',
                'expiry_reason' => 'cancelled',
            ],
        };

        // PaperVerificationCodes::validate
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($codeData)));

        // CombinedLpaManager::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        if ($this->viewerCode === 'P-1234-1234-1234-12') {
            // PaperVerificationCodes::expire
            $this->apiFixtures->append(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'expiry_date' => (new DateTimeImmutable('now'))->format('Y-m-d'),
                        ]
                    )
                )
            );
        }

        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('lpa-data-store-secret', $command['SecretId']);

                return new Result(['SecretString' => 'secret-value-string-at-least-128-bits-long']);
            }
        );

        $this->apiPost(
            '/v1/paper-verification/validate',
            [
                'code'          => $this->viewerCode,
                'name'          => $this->donorSurname,
                'lpaUid'        => $this->lpa->uid,
                'sentToDonor'   => $this->sentToDonor,
                'attorneyName'  => $this->attorneyName,
                'dateOfBirth'   => $this->dateOfBirth,
                'noOfAttorneys' => $this->noOfAttorneys,
            ],
        );
    }

    #[Given('I have access to an LPA via :a paper verification code')]
    public function iHaveAccessToAnLPAViaAPaperVerificationCode(string $type): void
    {
        // this hardcoded stuff will be swapped out when the service stops hardcoding things
        $this->viewerCode = match ($type) {
            'a'           => 'P-1234-1234-1234-12',
            'an expired'  => 'P-5678-5678-5678-56',
            'a cancelled' => 'P-3456-3456-3456-34'
        };
        $this->donorSurname = 'Bundlaaaa';

        $this->lpa = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/4000.json'),
        );
    }

    #[Given('I have been given access to an LPA via share code')]
    public function iHaveBeenGivenAccessToUseAnLPAViaShareCode(): void
    {
        $this->viewerCode             = '111111111111';
        $this->donorSurname           = 'Deputy';
        $this->viewerCodeOrganisation = 'santander';
        $this->lpaViewedBy            = 'Santander';

        $this->lpa = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'),
        );
    }

    #[Given('I have been given access to a cancelled LPA via share code')]
    public function iHaveBeenGivenAccessToUseACancelledLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaShareCode();
        $this->lpa->status = 'Cancelled';
    }

    #[Given('I access the viewer service')]
    public function iAccessTheViewerService(): void
    {
        // Not used in this context
    }

    #[When("I give a share code that's been cancelled")]
    public function iGiveAShareCodeThatHasBeenCancelled(): void
    {
        $lpaExpiry = (new DateTime('+20 days'))->format('c');
        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa->uId,
                            'Added'      => (new DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry,
                            'Cancelled'  => (new DateTime('now'))->format('c'),
                        ]
                    ),
                ]
            )
        );

        // SiriusLpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/viewer-codes/summary',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname,
            ]
        );
    }

    #[Then('I can see a message the LPA has been cancelled')]
    public function iCanSeeAMessageTheLPAHasBeenCancelled(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_GONE);
        $lpaData = $this->getResponseAsJson();
        Assert::assertEquals($lpaData['title'], 'Gone');
        Assert::assertStringContainsString('cancelled', $lpaData['details']);
    }

    #[Then('I am told that the paper verification code has been cancelled')]
    public function iAmToldThatThePaperVerificationCodeHasBeenCancelled(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_GONE);
        $lpaData = $this->getResponseAsJson();
        Assert::assertEquals($lpaData['title'], 'Gone');
        Assert::assertEquals('cancelled', $lpaData['data']['reason']);
    }

    #[Then('I am told that the paper verification code has expired')]
    public function iCanSeeAMessageTheLPAHasBeenExpired(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_GONE);
        $lpaData = $this->getResponseAsJson();
        Assert::assertEquals($lpaData['title'], 'Gone');
        Assert::assertEquals('first_time_use', $lpaData['data']['reason']);
    }

    #[Given('/^I can see (.*) images$/')]
    public function iCanSeeInstructionsImages(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('iap', $lpaData);
        Assert::assertEquals((int) $this->lpa->uId, $lpaData['iap']['uId']);
        Assert::assertEquals('COLLECTION_COMPLETE', $lpaData['iap']['status']);
    }

    #[When('I give a valid LPA share code')]
    public function iGiveAValidLPAShareCode(): void
    {
        $lpaExpiry = (new DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode'   => $this->viewerCode,
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => (new DateTime('now'))->format('c'),
                            'Expires'      => $lpaExpiry,
                            'Organisation' => $this->viewerCodeOrganisation,
                        ]
                    ),
                ]
            )
        );

        // SiriusLpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $imageResponse             = new stdClass();
        $imageResponse->uId        = (int) $this->lpa->uId;
        $imageResponse->status     = 'COLLECTION_COMPLETE';
        $imageResponse->signedUrls = [
            'iap-' . $this->lpa->uId . '-instructions' => 'https://image_url',
            'iap-' . $this->lpa->uId . '-preferences'  => 'https://image_url',
        ];

        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($imageResponse),
            )
        );

        $this->apiPost(
            '/v1/viewer-codes/summary',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname,
            ]
        );
    }

    #[When('/^I enter an organisation name and confirm the LPA is correct$/')]
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);

        $lpaExpiry = (new DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode'   => $this->viewerCode,
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => (new DateTime('now'))->format('c'),
                            'Expires'      => $lpaExpiry,
                            'Organisation' => $this->viewerCodeOrganisation,
                        ]
                    ),
                ]
            )
        );

        // SiriusLpas::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // ViewerCodeActivity::recordSuccessfulLookupActivity
        $this->awsFixtures->append(new Result([]));

        $this->apiPost(
            '/v1/viewer-codes/full',
            [
                'code'         => $this->viewerCode,
                'name'         => $this->donorSurname,
                'organisation' => $this->lpaViewedBy,
            ]
        );
    }

    #[Then('I can see the full details of the valid LPA')]
    public function iCanSeeTheFullDetailsOfTheValidLPA(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);
    }

    #[Given('I provide :sentToDonor, :attorneyName, :dateOfBirth and :noOfAttorneys as my information')]
    public function iProvideSentToDonorAttorneyNameDateOfBirthAndNoOfAttorneys(
        string $sentToDonor,
        string $attorneyName,
        string $dateOfBirth,
        string $noOfAttorneys,
    ): void {
        $this->sentToDonor   = filter_var($sentToDonor, FILTER_VALIDATE_BOOLEAN);
        $this->attorneyName  = $attorneyName;
        $this->dateOfBirth   = $dateOfBirth;
        $this->noOfAttorneys = intval($noOfAttorneys);
    }

    #[When('I provide donor surname and paper verification code')]
    public function iProvideDonorSurnameAndPaperVerificationCode(): void
    {
        $codeData = match ($this->viewerCode) {
            'P-1234-1234-1234-12' => [
                'lpa'   => 'M-7890-0400-4000',
                'actor' => '',
            ],
            'P-5678-5678-5678-56' => [
                'lpa'           => 'M-7890-0400-4000',
                'actor'         => '',
                'expiry_date'   => '2024-12-13',
                'expiry_reason' => 'first_time_use',
            ],
            'P-3456-3456-3456-34' => [
                'lpa'           => 'M-7890-0400-4000',
                'actor'         => '',
                'expiry_date'   => '2025-10-24',
                'expiry_reason' => 'cancelled',
            ],
        };

        // PaperVerificationCodes::validate
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($codeData)));

        // CombinedLpaManager::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->awsFixtures->append(
            function (Command $command): ResultInterface {
                Assert::assertEquals('GetSecretValue', $command->getName());
                Assert::assertEquals('lpa-data-store-secret', $command['SecretId']);

                return new Result(['SecretString' => 'secret-value-string-at-least-128-bits-long']);
            }
        );

        $this->apiPost(
            '/v1/paper-verification/usable',
            [
                'code' => $this->viewerCode,
                'name' => $this->donorSurname,
            ]
        );
    }

    #[Given('I provide the correct code holders date of birth, number of attorneys and attorney name')]
    public function iProvideTheCorrectDateOfBirthNoOfAttorneysAndAttorneyName(): void
    {
        // fixture data includes an inactive attorney so we minus a 1.
        $this->iProvideSentToDonorAttorneyNameDateOfBirthAndNoOfAttorneys(
            'false',
            $this->lpa->attorneys[0]->firstNames . ' ' . $this->lpa->attorneys[0]->lastName,
            $this->lpa->attorneys[0]->dateOfBirth,
            (string) (count($this->lpa->attorneys) + count($this->lpa->trustCorporations) - 1)
        );
    }

    #[Then('/^I see a message that LPA has been cancelled$/')]
    public function iSeeAMessageThatLPAHasBeenCancelled(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('expires', $lpaData);
        Assert::assertArrayHasKey('organisation', $lpaData);
        Assert::assertArrayHasKey('lpa', $lpaData);

        Assert::assertEquals($this->donorSurname, $lpaData['lpa']['donor']['surname']);
        Assert::assertEquals('Cancelled', $lpaData['lpa']['status']);
    }

    #[When('/^I realise the LPA is incorrect$/')]
    public function iRealiseTheLPAIsCorrect(): void
    {
        // Not used in this context
    }

    #[Then('/^I want to see an option to re-enter code$/')]
    public function iWantToSeeAnOptionToReEnterCode(): void
    {
        // Not used in this context
    }

    #[Then('/^I want to see an option to check another LPA$/')]
    public function iWantToSeeAnOptionToCheckAnotherLPA(): void
    {
        // Not used in this context
    }

    #[Then('I will be asked to enter an organisation name')]
    public function iWillBeAskedToEnterAnOrganisationName(): void
    {
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $lpaData = $this->getResponseAsJson();

        // 'expiresAt' and 'expiryReason' are optional in the response so best not check them
        Assert::assertArrayHasKey('donorName', $lpaData);
        Assert::assertArrayHasKey('type', $lpaData);
        Assert::assertArrayHasKey('status', $lpaData);
    }

    #[Given('/^the LPA has (.*)$/')]
    public function theLPAHasDirective(string $directive): void
    {
        $this->lpa->applicationHasRestrictions = false;
        $this->lpa->applicationHasGuidance     = false;
        if (str_contains($directive, 'instructions')) {
            $this->lpa->applicationHasRestrictions = true;
        }
        if (str_contains($directive, 'preferences')) {
            $this->lpa->applicationHasGuidance = true;
        }
    }

    #[Given('the paper verification code expiry timer is started if necessary')]
    public function thePaperVerificationCodeExpiryTimerIsStartedIfNecessary(): void
    {
        $this->apiFixtures->getLastRequest();
        throw new PendingException();
    }
}
