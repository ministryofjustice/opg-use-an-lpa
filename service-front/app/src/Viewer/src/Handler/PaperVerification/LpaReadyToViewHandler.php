<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\Organisation;
use Viewer\Handler\AbstractPVSCodeHandler;
use Common\Service\Lpa\Factory\{LpaDataFormatter};

/**
 * @codeCoverageIgnore
 */
class LpaReadyToViewHandler extends AbstractPVSCodeHandler
{
    private Organisation $form;

    public const TEMPLATE = 'viewer::paper-verification/enter-organisation-name';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private SystemMessageService $systemMessageService,
        private LpaDataFormatter $lpaDataFormatter,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO - Remove temporary code and get from session
       $this->code    = ($this->getSession($request, 'session')->get('lpa_code')) ?? 'P-AB12-CD34-EF56-G7';
       $this->surname = ($this->getSession($request, 'session')->get('donor_surname')) ?? 'Babara Gilson';

        $this->form           = new Organisation($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
       if (isset($this->code)) {
            // TODO - LPA service call to check lpa match
            //$lpa = $this->lpaService->getLpaByLpaCode($this->code, $this->surname, null);

            // mocking Lpa data for testing page
            $lpa = json_decode(file_get_contents(__DIR__ . '../../../../../../test/fixtures/combined_lpa.json'), true);
            $combinedSiriusLpa = ($this->lpaDataFormatter)($lpa);
            //
       }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'       => $this->form->prepare(),
            'donor_name' => $combinedSiriusLpa->getDonor()->getFirstname() . ' ' . $combinedSiriusLpa->getDonor()->getSurname(),
            'lpa_type'   => $combinedSiriusLpa->getLpaType(),
            'back'       => $this->lastPage($this->state($request)),
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->session->set('organisation', $this->form->getData()['organisation']);

            $this->state($request)->organisation = $this->form->getData()['organisation'];
            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'       => $this->form->prepare(),
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
       return false;
//        return $this->state($request)->lastName === null
//            || $this->state($request)->code === null
//            || $this->state($request)->lpaUid === null
//            || $this->state($request)->sentToDonor === null
//            || $this->state($request)->sentToDonor === false
//            || $this->state($request)->attorneyName === null
//            || $this->state($request)->noOfAttorneys === 0
//            || $this->state($request)->noOfAttorneys === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        //needs changing when next page ready
        return 'home';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        //needs changing when next page ready
        return 'home';
    }
}
