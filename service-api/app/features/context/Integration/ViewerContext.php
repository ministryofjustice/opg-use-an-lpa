<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;
use App\Exception\GoneException;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use BehatTest\Context\UsesPactContextTrait;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;
use stdClass;

/**
 * @property string viewerCode   The share code given to an organisation
 * @property string donorSurname The surname of the donors LPA reference by the share code
 * @property array  lpa          LPA data as returned by the API gateway
 * @property string lpaViewedBy  The organisation that the lpa has been viewed by using the share code
 * @property string organisation The organisation that the lpa was created for
 */
class ViewerContext extends BaseIntegrationContext
{
    use SetupEnv;
    use UsesPactContextTrait;

    private string $apiGatewayPactProvider;
    private string $iapImagesPactProvider;
    private AwsMockHandler $awsFixtures;
    private LpaService $lpaService;

    /**
     * @Given I access the viewer service
     */
    public function iAccessTheViewerService(): void
    {
        // Not used in this context
    }

    /**
     * @Given /^I can see instructions images$/
     */
    public function iCanSeeInstructionsImages()
    {
        // Not used in this context
    }

    /**
     * @Then I can see the full details of the valid LPA
     */
    public function iCanSeeTheFullDetailsOfTheValidLPA(): void
    {
        // Not used in this context
    }

    /**
     * @When /^I enter an organisation name and confirm the LPA is correct$/
     */
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect(): void
    {
        $this->organisation = 'TestOrg';

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
                            'Organisation' => $this->organisation,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodeActivity::recordSuccessfulLookupActivity
        $this->awsFixtures->append(new Result([]));

        // organisation parameter is a string when doing a full check
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, $this->lpaViewedBy);

        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($lpaExpiry, $lpaData['expires']);
    }

    /**
     * @When I give a share code that's been cancelled
     */
    public function iGiveAShareCodeThatHasBeenCancelled(): void
    {
        $lpaExpiry         = (new DateTime('+20 days'))->format('c');
        $this->lpa->status = 'Cancelled';

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

        // Lpas::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpa->uId,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        // organisation parameter is null when doing a summary check
        try {
            $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, null);
        } catch (GoneException) {
            return;
        }

        throw new Exception('GoneException not thrown when it should be');
    }

    /**
     * @When I give a valid LPA share code
     */
    public function iGiveAValidLPAShareCode(): void
    {
        $this->organisation = 'TestOrg';

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
                            'Organisation' => $this->organisation,
                        ]
                    ),
                ]
            )
        );

        // Lpas::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpa->uId,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        if ($this->container->get(FeatureEnabled::class)('instructions_and_preferences')) {
            $imageResponse             = new stdClass();
            $imageResponse->uId        = (int) $this->lpa->uId;
            $imageResponse->status     = 'COLLECTION_COMPLETE';
            $imageResponse->signedUrls = [
                'iap-' . $this->lpa->uId . '-instructions' => 'https://image_url',
                'iap-' . $this->lpa->uId . '-preferences'  => 'https://image_url',
            ];

            $this->pactGetInteraction(
                $this->iapImagesPactProvider,
                '/v1/image-request/' . $this->lpa->uId,
                StatusCodeInterface::STATUS_OK,
                $imageResponse
            );
        }

        // organisation parameter is null when doing a summary check
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, null);

        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($lpaExpiry, $lpaData['expires']);

        if ($this->container->get(FeatureEnabled::class)('instructions_and_preferences')) {
            Assert::assertArrayHasKey('iap', $lpaData);
            Assert::assertInstanceOf(InstructionsAndPreferencesImages::class, $lpaData['iap']);
            Assert::assertEquals('COLLECTION_COMPLETE', $lpaData['iap']->status->value);
        }
    }

    /**
     * @Given I have been given access to a cancelled LPA via share code
     */
    public function iHaveBeenGivenAccessToUseACancelledLPAViaShareCode(): void
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaShareCode();
        $this->lpa->status = 'Cancelled';
    }

        /**
         * @Given I have been given access to an LPA via share code
         */
    public function iHaveBeenGivenAccessToUseAnLPAViaShareCode(): void
    {
        $this->viewerCode   = '1111-1111-1111';
        $this->donorSurname = 'Deputy';
        $this->lpaViewedBy  = 'Santander';
        $this->lpa          = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json')
        );
    }

        /**
         * @When /^I realise the LPA is incorrect$/
         */
    public function iRealiseTheLPAIsCorrect(): void
    {
        // Not used in this context
    }

        /**
         * @Then /^I can see a message the LPA has been cancelled$/
         */
    public function iSeeAMessageThatLPAHasBeenCancelled(): void
    {
        // Not used in this context
    }

        /**
         * @Then /^I want to see an option to check another LPA$/
         */
    public function iWantToSeeAnOptionToCheckAnotherLPA(): void
    {
        // Not used in this context
    }

        /**
         * @Then /^I want to see an option to re-enter code$/
         */
    public function iWantToSeeAnOptionToReEnterCode(): void
    {
        // Not used in this context
    }

        /**
         * @Given /^the LPA has (.*)$/
         */
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

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);

        $this->lpaService = $this->lpaService = $this->container->get(LpaService::class);

        $config                       = $this->container->get('config');
        $this->apiGatewayPactProvider = parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST);
        $this->iapImagesPactProvider  = parse_url($config['iap_images_api']['endpoint'], PHP_URL_HOST);
    }
}
