<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;

class Lpa
{
    protected int $id;
    protected ?string $uId                                     = null;
    protected ?string $applicationType                         = null;
    protected ?string $caseSubtype                             = null;
    protected ?DateTime $receiptDate                           = null;
    protected ?DateTime $rejectedDate                          = null;
    protected ?DateTime $cancellationDate                      = null;
    protected ?DateTime $registrationDate                      = null;
    protected ?string $status                                  = null;
    protected ?bool $caseAttorneySingular                      = null;
    protected ?bool $caseAttorneyJointlyAndSeverally           = null;
    protected ?bool $caseAttorneyJointly                       = null;
    protected ?bool $caseAttorneyJointlyAndJointlyAndSeverally = null;
    protected ?bool $applicationHasRestrictions                = null;
    protected ?bool $applicationHasGuidance                    = null;
    protected ?DateTime $lpaDonorSignatureDate                 = null;
    protected ?string $lifeSustainingTreatment                 = null;
    protected ?string $onlineLpaId                             = null;
    protected ?string $attorneyActDecisions                    = null;
    protected ?CaseActor $donor                                = null;

    /** @var CaseActor[] */
    protected array $attorneys = [];

    /** @var CaseActor[] */
    protected array $replacementAttorneys = [];

    /** @var CaseActor[] */
    protected array $certificateProviders = [];

    /** @var CaseActor[] */
    protected array $trustCorporations = [];

    /** @var CaseActor[] */
    protected array $activeAttorneys = [];

    /** @var CaseActor[] */
    protected array $inactiveAttorneys = [];

    public function getUId(): ?string
    {
        return $this->uId;
    }

    public function setUId(string $uId): void
    {
        $this->uId = $uId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getApplicationType(): ?string
    {
        return $this->applicationType;
    }

    public function setApplicationType(string $applicationType): void
    {
        $this->applicationType = $applicationType;
    }

    public function getCaseSubtype(): ?string
    {
        return $this->caseSubtype;
    }

    public function setCaseSubtype(string $caseSubtype): void
    {
        $this->caseSubtype = $caseSubtype;
    }

    public function getReceiptDate(): ?DateTime
    {
        return $this->receiptDate;
    }

    public function setReceiptDate(DateTime $receiptDate): void
    {
        $this->receiptDate = $receiptDate;
    }

    public function getRejectedDate(): ?DateTime
    {
        return $this->rejectedDate;
    }

    public function setRejectedDate(DateTime $rejectedDate): void
    {
        $this->rejectedDate = $rejectedDate;
    }

    public function getRegistrationDate(): ?DateTime
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(DateTime $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCaseAttorneySingular(): ?bool
    {
        return $this->caseAttorneySingular;
    }

    public function setCaseAttorneySingular(bool $caseAttorneySingular): void
    {
        $this->caseAttorneySingular = $caseAttorneySingular;
    }

    public function getCaseAttorneyJointlyAndSeverally(): ?bool
    {
        return $this->caseAttorneyJointlyAndSeverally;
    }

    public function setCaseAttorneyJointlyAndSeverally(bool $caseAttorneyJointlyAndSeverally): void
    {
        $this->caseAttorneyJointlyAndSeverally = $caseAttorneyJointlyAndSeverally;
    }

    public function getCaseAttorneyJointly(): ?bool
    {
        return $this->caseAttorneyJointly;
    }

    public function setCaseAttorneyJointly(bool $caseAttorneyJointly): void
    {
        $this->caseAttorneyJointly = $caseAttorneyJointly;
    }

    public function getCaseAttorneyJointlyAndJointlyAndSeverally(): ?bool
    {
        return $this->caseAttorneyJointlyAndJointlyAndSeverally;
    }

    public function setCaseAttorneyJointlyAndJointlyAndSeverally(bool $caseAttorneyJointlyAndJointlyAndSeverally): void
    {
        $this->caseAttorneyJointlyAndJointlyAndSeverally = $caseAttorneyJointlyAndJointlyAndSeverally;
    }

    public function getApplicationHasRestrictions(): ?bool
    {
        return $this->applicationHasRestrictions;
    }

    public function setApplicationHasRestrictions(bool $applicationHasRestrictions): void
    {
        $this->applicationHasRestrictions = $applicationHasRestrictions;
    }

    public function getApplicationHasGuidance(): ?bool
    {
        return $this->applicationHasGuidance;
    }

    public function setApplicationHasGuidance(bool $applicationHasGuidance): void
    {
        $this->applicationHasGuidance = $applicationHasGuidance;
    }

    public function setApplicationHasSeveranceWarning(bool $hasSeveranceWarning): void
    {
        $this->hasSeveranceWarning = $hasSeveranceWarning;
    }

    public function getLpaDonorSignatureDate(): ?DateTime
    {
        return $this->lpaDonorSignatureDate;
    }

    public function setLpaDonorSignatureDate(DateTime $lpaDonorSignatureDate): void
    {
        $this->lpaDonorSignatureDate = $lpaDonorSignatureDate;
    }

    public function getLifeSustainingTreatment(): ?string
    {
        return $this->lifeSustainingTreatment;
    }

    public function setLifeSustainingTreatment(?string $lifeSustainingTreatment): void
    {
        $this->lifeSustainingTreatment = $lifeSustainingTreatment;
    }

    public function getOnlineLpaId(): ?string
    {
        return $this->onlineLpaId;
    }

    public function setOnlineLpaId(string $onlineLpaId): void
    {
        $this->onlineLpaId = $onlineLpaId;
    }

    public function getAttorneyActDecisions(): ?string
    {
        return $this->attorneyActDecisions;
    }

    public function setAttorneyActDecisions(string $attorneyActDecisions): void
    {
        $this->attorneyActDecisions = $attorneyActDecisions;
    }

    public function getDonor(): ?CaseActor
    {
        return $this->donor;
    }

    public function setDonor(CaseActor $donor): void
    {
        $this->donor = $donor;
    }

    public function getAttorneys(): array
    {
        return $this->attorneys;
    }

    public function setAttorneys(array $attorneys): void
    {
        $this->attorneys = $attorneys;
    }

    public function getReplacementAttorneys(): array
    {
        return $this->replacementAttorneys;
    }

    public function setReplacementAttorneys(array $replacementAttorneys): void
    {
        $this->replacementAttorneys = $replacementAttorneys;
    }

    public function getCertificateProviders(): array
    {
        return $this->certificateProviders;
    }

    public function setCertificateProviders(array $certificateProviders): void
    {
        $this->certificateProviders = $certificateProviders;
    }

    public function getActiveAttorneys(): array
    {
        return $this->activeAttorneys;
    }

    public function setActiveAttorneys(array $activeAttorneys): void
    {
        $this->activeAttorneys = $activeAttorneys;
    }

    public function getInactiveAttorneys(): array
    {
        return $this->inactiveAttorneys;
    }

    public function setInactiveAttorneys(array $inactiveAttorneys): void
    {
        $this->inactiveAttorneys = $inactiveAttorneys;
    }

    public function getTrustCorporations(): array
    {
        return $this->trustCorporations;
    }

    public function setTrustCorporations(array $trustCorporations): void
    {
        $this->trustCorporations = $trustCorporations;
    }

    public function setCancellationDate(DateTime $date)
    {
        $this->cancellationDate = $date;
    }

    public function getCancellationDate(): ?DateTime
    {
        return $this->cancellationDate;
    }
}
