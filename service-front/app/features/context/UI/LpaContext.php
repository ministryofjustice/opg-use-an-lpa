<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use Psr\Http\Message\RequestInterface;

/**
 * Class LpaContext
 * @package BehatTest\Context\UI
 *
 * @property $lpa
 * @property $lpaData
 * @property $userId
 * @property $userLpaActorToken
 * @property $userActive
 * @property $actorId
 * @property $accessCode
 * @property $organisation
 * @property $newUserEmail
 * @property $userEmailResetToken
 * @property $activationToken
 * @property $userPostCode
 * @property $userFirstname
 * @property $userSurname
 * @property $userDOB
 */
class LpaContext extends BaseUiContext
{
    use ActorContext;

    /** @var LpaFactory */
    private $lpaFactory;
    /** @var LpaService */
    private $lpaService;
    /** @var ViewerCodeService */
    private $viewerCodeService;



    /**
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument()
    {
        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->userPostCode = 'string';
        $this->userFirstname = 'Ian Deputy';
        $this->userSurname = 'Deputy';
        $this->userDOB = '1975-10-05';
        $this->lpa->registrationDate = '2019-09-01';
    }




}
