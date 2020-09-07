<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Factory;

use Common\Service\Lpa\LpaFactory;
use Common\Entity\Lpa;
use Common\Entity\CaseActor;
use Common\Entity\Address;
use Exception;
use Laminas\Stdlib\Exception\InvalidArgumentException;
use DateTime;

use function filter_var;

final class Sirius implements LpaFactory
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
     * @throws InvalidArgumentException|Exception
     */
    public function createLpaFromData(array $data): Lpa
    {
        if (!array_key_exists('uId', $data)) {
            throw new InvalidArgumentException("The data array passed to " . __METHOD__ . " must contain valid Lpa data");
        }

        $lpa = new Lpa();
        if (isset($data['id'])) {
            $lpa->setId($data['id']);
        }
        if (isset($data['uId'])) {
            $lpa->setUId($data['uId']);
        }
        if (isset($data['applicationType'])) {
            $lpa->setApplicationType($data['applicationType']);
        }
        if (isset($data['caseSubtype'])) {
            $lpa->setCaseSubtype($data['caseSubtype']);
        }
        if (isset($data['receiptDate'])) {
            $lpa->setReceiptDate(new DateTime($data['receiptDate']));
        }
        if (isset($data['rejectedDate'])) {
            $lpa->setRejectedDate(new DateTime($data['rejectedDate']));
        }
        if (isset($data['registrationDate'])) {
            $lpa->setRegistrationDate(new DateTime($data['registrationDate']));
        }
        if (isset($data['status'])) {
            $lpa->setStatus($data['status']);
        }
        if (isset($data['caseAttorneySingular'])) {
            $lpa->setCaseAttorneySingular($data['caseAttorneySingular']);
        }
        if (isset($data['caseAttorneyJointlyAndSeverally'])) {
            $lpa->setCaseAttorneyJointlyAndSeverally($data['caseAttorneyJointlyAndSeverally']);
        }
        if (isset($data['caseAttorneyJointly'])) {
            $lpa->setCaseAttorneyJointly($data['caseAttorneyJointly']);
        }
        if (isset($data['caseAttorneyJointlyAndJointlyAndSeverally'])) {
            $lpa->setCaseAttorneyJointlyAndJointlyAndSeverally($data['caseAttorneyJointlyAndJointlyAndSeverally']);
        }
        if (isset($data['applicationHasRestrictions'])) {
            $lpa->setApplicationHasRestrictions($data['applicationHasRestrictions']);
        }
        if (isset($data['applicationHasGuidance'])) {
            $lpa->setApplicationHasGuidance($data['applicationHasGuidance']);
        }
        if (isset($data['lpaDonorSignatureDate'])) {
            $lpa->setLpaDonorSignatureDate(new DateTime($data['lpaDonorSignatureDate']));
        }

        if (isset($data['lifeSustainingTreatment'])) {
            $lpa->setLifeSustainingTreatment(($data['lifeSustainingTreatment']));
        }

        if (isset($data['onlineLpaId'])) {
            $lpa->setOnlineLpaId($data['onlineLpaId']);
        }
        if (isset($data['attorneyActDecisions'])) {
            $lpa->setAttorneyActDecisions($data['attorneyActDecisions']);
        }

        if (isset($data['donor'])) {
            $lpa->setDonor($this->createCaseActorFromData($data['donor']));
        }
        if (isset($data['attorneys'])) {
            $lpa->setAttorneys($this->createCaseActorsFromData($data['attorneys']));
        }
        if (isset($data['replacementAttorneys'])) {
            $lpa->setReplacementAttorneys($this->createCaseActorsFromData($data['replacementAttorneys']));
        }
        if (isset($data['trustCorportations'])) {
            $lpa->setTrustCorporations($this->createCaseActorsFromData($data['trustCorportations']));
        }
        if (isset($data['certificateProviders'])) {
            $lpa->setCertificateProviders($this->createCaseActorsFromData($data['certificateProviders']));
        }
        if (isset($data['cancellationDate'])) {
            $lpa->setCancellationDate(new DateTime($data['cancellationDate']));
        }
        return $lpa;
    }

    /**
     * @param array $caseActorData
     * @return CaseActor
     * @throws Exception
     */
    public function createCaseActorFromData(array $caseActorData): CaseActor
    {
        if (!array_key_exists('uId', $caseActorData)) {
            throw new InvalidArgumentException("The data array passed to " . __METHOD__ . " must contain valid CaseActor data");
        }

        $actor = new CaseActor();
        if (isset($caseActorData['id'])) {
            $actor->setId($caseActorData['id']);
        }
        if (isset($caseActorData['uId'])) {
            $actor->setUId($caseActorData['uId']);
        }
        if (isset($caseActorData['email'])) {
            $actor->setEmail($caseActorData['email']);
        }
        if (isset($caseActorData['dob'])) {
            $actor->setDob(new DateTime($caseActorData['dob']));
        }
        if (isset($caseActorData['salutation'])) {
            $actor->setSalutation($caseActorData['salutation']);
        }
        if (isset($caseActorData['firstname'])) {
            $actor->setFirstname($caseActorData['firstname']);
        }
        if (isset($caseActorData['middlenames'])) {
            $actor->setMiddlenames($caseActorData['middlenames']);
        }
        if (isset($caseActorData['surname'])) {
            $actor->setSurname($caseActorData['surname']);
        }
        if (isset($caseActorData['companyName'])) {
            $actor->setCompanyName($caseActorData['companyName']);
        }
        if (isset($caseActorData['systemStatus'])) {
            $actor->setSystemStatus($caseActorData['systemStatus']);
        }
        if (isset($caseActorData['addresses'])) {
            $actor->setAddresses($this->createAddressesFromData($caseActorData['addresses']));
        }

        return $actor;
    }

    /**
     * @param array $addressData
     * @return Address
     * @throws InvalidArgumentException
     */
    public function createAddressFromData(array $addressData): Address
    {
        if (!array_key_exists('id', $addressData)) {
            throw new InvalidArgumentException("The data array passed to " . __METHOD__ . " must contain valid Address data");
        }
        $address = new Address();
        if (isset($addressData['id'])) {
            $address->setId($addressData['id']);
        }
        if (isset($addressData['town'])) {
            $address->setTown($addressData['town']);
        }
        if (isset($addressData['county'])) {
            $address->setCounty($addressData['county']);
        }
        if (isset($addressData['postcode'])) {
            $address->setPostcode($addressData['postcode']);
        }
        if (isset($addressData['country'])) {
            $address->setCountry($addressData['country']);
        }
        if (isset($addressData['type'])) {
            $address->setType($addressData['type']);
        }
        if (isset($addressData['addressLine1'])) {
            $address->setAddressLine1($addressData['addressLine1']);
        }
        if (isset($addressData['addressLine2'])) {
            $address->setAddressLine2($addressData['addressLine2']);
        }
        if (isset($addressData['addressLine3'])) {
            $address->setAddressLine3($addressData['addressLine3']);
        }

        return $address;
    }

    private function createCaseActorsFromData(array $caseActorsData): array
    {
        $actors = [];

        foreach ($caseActorsData as $caseActor) {
            $actors[] = $this->createCaseActorFromData($caseActor);
        }

        return $actors;
    }

    private function createAddressesFromData(array $addressesData): array
    {
        $addresses = [];

        foreach ($addressesData as $address) {
            $addresses[] = $this->createAddressFromData($address);
        }

        return $addresses;
    }
}
