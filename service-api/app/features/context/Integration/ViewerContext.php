<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;

/**
 * Class ViewerContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property string viewerCode The share code given to an organisation
 * @property string donorSurname The surname of the donors LPA reference by the share code
 * @property array lpa LPA data as returned by the API gateway
 */
class ViewerContext extends BaseIntegrationContext
{
    use SetupEnv;

    /** @var MockHandler */
    private $apiFixtures;

    /** @var AwsMockHandler */
    private $awsFixtures;

    /** @var LpaService */
    private $lpaService;

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);

        $this->lpaService = $this->lpaService = $this->container->get(LpaService::class);
    }

    /**
     * @Given I have been given access to an LPA via share code
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaShareCode()
    {
        $this->viewerCode = '1111-1111-1111';
        $this->donorSurname = 'Deputy';
        $this->lpa = json_decode(
            file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'),
            true
        );
    }

    /**
     * @Given I have been given access to a cancelled LPA via share code
     */
    public function iHaveBeenGivenAccessToUseACancelledLPAViaShareCode()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaShareCode();
        $this->lpa['status'] = 'Cancelled';
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
                            'SiriusUid'  => $this->lpa['uId'],
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry
                        ]
                    )
                ]
            )
        );

        // Lpas::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpa['uId'])
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // logActivity parameter is false when doing a summary check
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, false);

        assertEquals($this->lpa, $lpaData['lpa']);
        assertEquals($lpaExpiry, $lpaData['expires']);
    }

    /**
     * @When I confirm the LPA is correct
     */
    public function iConfirmTheLPAIsCorrect()
    {
        $lpaExpiry = (new \DateTime('+20 days'))->format('c');

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'ViewerCode' => $this->viewerCode,
                            'SiriusUid'  => $this->lpa['uId'],
                            'Added'      => (new \DateTime('now'))->format('c'),
                            'Expires'    => $lpaExpiry
                        ]
                    )
                ]
            )
        );

        // Lpas::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpa['uId'])
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // ViewerCodeActivity::recordSuccessfulLookupActivity
        $this->awsFixtures->append(new Result([]));

        // logActivity parameter is false when doing a summary check
        $lpaData = $this->lpaService->getByViewerCode($this->viewerCode, $this->donorSurname, true);

        assertEquals($this->lpa, $lpaData['lpa']);
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
     * @Then I can see the full details of a cancelled LPA
     */
    public function iCanSeeTheFullDetailsOfACancelledLPA()
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