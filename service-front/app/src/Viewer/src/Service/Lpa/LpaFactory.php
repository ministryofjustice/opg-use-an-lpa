<?php

declare(strict_types=1);

namespace Viewer\Service\Lpa;

use Viewer\Entity\Lpa;
use Viewer\Entity\CaseActor;
use Viewer\Entity\Address;
use \DateTime;
use Zend\Stdlib\Exception\InvalidArgumentException;

final class LpaFactory
{
    /**
     * Creates a Lpa from the supplied data array.
     * 
     * Anticipates a data array that follows the swagger spec. given at 
     * https://github.com/ministryofjustice/opg-sirius/blob/master/back-end/docs/api/swagger/api.public.v1.yaml
     * 
     * Some rudimentary checks are carried out on the suitability of data in the array but for the most
     * part it is assumed the caller has done the right thing with whats been pulled from Sirius.
     *
     * @param array $data
     * @return Lpa
     */
    public function createLpaFromData(array $data) : Lpa
    {
        if ( ! array_key_exists('caseNumber', $data)) {
            throw new InvalidArgumentException("The data array passed to " . __CLASS__ . " must contain valid Lpa data");
        }

        $lpa = new Lpa();
        $lpa->setId($data['id']);
        $lpa->setUId($data['uId']);
        $lpa->setApplicationType($data['applicationType']);
        $lpa->setCaseSubtype($data['caseSubtype']);
        $lpa->setReceiptDate($data['receiptDate'] ? new DateTime($data['receiptDate']) : null);
        $lpa->setRejectedDate($data['rejectedDate'] ? new DateTime($data['rejectedDate']) : null);
        $lpa->setRegistrationDate($data['registrationDate'] ? new DateTime($data['registrationDate']) : null);
        $lpa->setStatus($data['status']);
        $lpa->setCaseAttorneySingular($data['caseAttorneySingular']);
        $lpa->setCaseAttorneyJointlyAndSeverally($data['caseAttorneyJointlyAndSeverally']);
        $lpa->setCaseAttorneyJointly($data['caseAttorneyJointly']);
        $lpa->setCaseAttorneyJointlyAndJointlyAndSeverally($data['caseAttorneyJointlyAndJointlyAndSeverally']);
        $lpa->setApplicationHasRestrictions($data['applicationHasRestrictions']);
        $lpa->setApplicationHasGuidance($data['applicationHasGuidance']);
        $lpa->setLpaDonorSignatureDate($data['lpaDonorSignatureDate'] ? new DateTime($data['lpaDonorSignatureDate']) : null);
        $lpa->setLifeSustainingTreatment($data['lifeSustainingTreatment']);
        $lpa->setOnlineLpaId($data['onlineLpaId']);
        $lpa->setAttorneyActDecisions($data['attorneyActDecisions']);

        $lpa->setDonor($this->createCaseActorFromData($data['donor']));
        $lpa->setAttorneys($this->createCaseActorsFromData($data['attorneys']));
        $lpa->setReplacementAttorneys($this->createCaseActorsFromData($data['replacementAttorneys']));
        $lpa->setTrustCorporations($this->createCaseActorsFromData($data['trustCorportations']));
        $lpa->setCertificateProviders($this->createCaseActorsFromData($data['certificateProviders']));

        return $lpa;
    }

    private function createCaseActorFromData(array $caseActorData) : CaseActor
    {
        $actor = new CaseActor();

        $actor->setId($caseActorData['id']);
        $actor->setUId($caseActorData['uId']);
        $actor->setEmail($caseActorData['email']);
        $actor->setDob($caseActorData['dob'] ? new DateTime($caseActorData['dob']) : null);
        $actor->setSalutation($caseActorData['salutation']);
        $actor->setFirstname($caseActorData['firstname']);
        $actor->setMiddlenames($caseActorData['middlenames']);
        $actor->setSurname($caseActorData['surname']);
        $actor->setCompanyName($caseActorData['companyName']);
        $actor->setAddresses($this->createAddressesFromData($caseActorData['addresses']));

        return $actor;
    }
    
    private function createAddressFromData(array $addressData) : Address
    {
        $address = new Address();

        $address->setId($addressData['id']);
        $address->setTown($addressData['town']);
        $address->setCounty($addressData['county']);
        $address->setPostcode($addressData['postcode']);
        $address->setCountry($addressData['country']);
        $address->setType($addressData['type']);
        $address->setAddressLine1($addressData['addressLine1']);
        $address->setAddressLine2($addressData['addressLine2']);
        $address->setAddressLine3($addressData['addressLine3']);

        return $address;
    }

    private function createCaseActorsFromData(array $caseActorsData) : array
    {
        $actors = [];

        foreach ($caseActorsData as $caseActor) {
            $actors[] = $this->createCaseActorFromData($caseActor);
        }

        return $actors;
    }

    private function createAddressesFromData(array $addressesData) : array
    {
        $addresses = [];

        foreach ($addressesData as $address) {
            $addresses[] = $this->createAddressFromData($address);
        }

        return $addresses;
    }
}