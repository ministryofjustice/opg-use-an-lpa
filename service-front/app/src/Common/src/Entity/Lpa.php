<?php

declare(strict_types=1);

namespace Common\Entity;

use DateTime;

class Lpa
{
    /** @var int */
    protected $id;

    /** @var string|null */
    protected $uId = null;

    /** @var string|null */
    protected $applicationType = null;

    /** @var string|null */
    protected $caseSubtype = null;

    /** @var DateTime|null */
    protected $receiptDate = null;

    /** @var DateTime|null */
    protected $rejectedDate = null;

    /** @var DateTime|null */
    protected $cancellationDate = null;

    /** @var DateTime|null */
    protected $registrationDate = null;

    /** @var string|null */
    protected $status = null;

    /** @var bool|null */
    protected $caseAttorneySingular = null;

    /** @var bool|null */
    protected $caseAttorneyJointlyAndSeverally = null;

    /** @var bool|null */
    protected $caseAttorneyJointly = null;

    /** @var bool|null */
    protected $caseAttorneyJointlyAndJointlyAndSeverally = null;

    /** @var bool|null */
    protected $applicationHasRestrictions = null;

    /** @var bool|null */
    protected $applicationHasGuidance = null;

    /** @var DateTime|null */
    protected $lpaDonorSignatureDate = null;

    /** @var string|null */
    protected $lifeSustainingTreatment = null;

    /** @var string|null */
    protected $onlineLpaId = null;

    /** @var string|null */
    protected $attorneyActDecisions = null;

    /** @var CaseActor|null */
    protected $donor = null;

    /** @var CaseActor[] */
    protected $attorneys = [];

    /** @var CaseActor[] */
    protected $replacementAttorneys = [];

    /** @var CaseActor[] */
    protected $certificateProviders = [];

    /** @var CaseActor[] */
    protected $trustCorporations = [];

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

    public function getCancellationDate() : ?DateTime
    {
        return $this->cancellationDate;
    }
}
