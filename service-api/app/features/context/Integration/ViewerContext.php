<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Exception\GoneException;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use BehatTest\Context\UsesPactContextTrait;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Class ViewerContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property string viewerCode The share code given to an organisation
 * @property string donorSurname The surname of the donors LPA reference by the share code
 * @property array lpa LPA data as returned by the API gateway
 * @property string lpaViewedBy The organisation that the lpa has been viewed by using the share code
 */
class ViewerContext extends BaseIntegrationContext
{
    use SetupEnv;
    use UsesPactContextTrait;

    private AwsMockHandler $awsFixtures;
    private LpaService $lpaService;
    private string $apiGatewayPactProvider;

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);

        $this->lpaService = $this->lpaService = $this->container->get(LpaService::class);

        $config = $this->container->get('config');
        $this->apiGatewayPactProvider = parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST);
    }

    /**
     * @Given I have been given access to an LPA via share code
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaShareCode()
    {
        $this->viewerCode = '1111-1111-1111';
        $this->donorSurname = 'Deputy';
        $this->lpaViewedBy = 'Santander';
        $this->lpa = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json')
        );
    }

    /**
     * @Given I have been given access to a cancelled LPA via share code
     */
    public function iHaveBeenGivenAccessToUseACancelledLPAViaShareCode()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaShareCode();
        $this->lpa->status = 'Cancelled';
    }

    /**
     * @Given I access the viewer service
     */
    public function iAccessTheViewerService()
    {
        // Not used in this context
    }

    /**
     * @When I give a valid LPA share code
     */
    public function iGiveAValidLPAShareCode()
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa->uId,
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry
                        ]
                    )
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
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, null);

        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($lpaExpiry, $lpaData['expires']);
    }

    /**
     * @When I give a share code that's been cancelled
     */
    public function iGiveAShareCodeThatHasBeenCancelled()
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');
        $this->lpa->status = 'Cancelled';

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa->uId,
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry,
                            'Cancelled'  => (new \DateTime('now'))->format('c'),
                        ]
                    )
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
        } catch (GoneException $gox) {
            return;
        }

        throw new \Exception('GoneException not thrown when it should be');
    }

    /**
     * @When /^I enter an organisation name and confirm the LPA is correct$/
     */
    public function iEnterAnOrganisationNameAndConfirmTheLPAIsCorrect()
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa->uId,
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry
                        ]
                    )
                ]
            )
        );

        // ViewerCodeActivity::recordSuccessfulLookupActivity
        $this->awsFixtures->append(new Result([]));

        // organisation parameter is a string when doing a full check
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, $this->lpaViewedBy);

        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($lpaExpiry, $lpaData['expires']);
    }

    /**
     * @Then I can see the full details of the valid LPA
     */
    public function iCanSeeTheFullDetailsOfTheValidLPA()
    {
        // Not used in this context
    }

    /**
     * @Then /^I can see a message the LPA has been cancelled$/
     */
    public function iSeeAMessageThatLPAHasBeenCancelled()
    {
        // Not used in this context
    }

    /**
     * @When /^I realise the LPA is incorrect$/
     */
    public function iRealiseTheLPAIsCorrect()
    {
        // Not used in this context
    }

    /**
     * @Then /^I want to see an option to re-enter code$/
     */
    public function iWantToSeeAnOptionToReEnterCode()
    {
        // Not used in this context
    }

    /**
     * @Then /^I want to see an option to check another LPA$/
     */
    public function iWantToSeeAnOptionToCheckAnotherLPA()
    {
        // Not used in this context
    }
}
