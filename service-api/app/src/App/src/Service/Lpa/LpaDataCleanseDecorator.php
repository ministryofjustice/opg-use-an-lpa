<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\Response\LpaInterface;
use DateTime;

class LpaDataCleanseDecorator implements LpaInterface
{
    /**
     * @var LpaInterface
     */
    private $lpa;

    public function __construct(LpaInterface $lpa)
    {
        $this->lpa = $lpa;
    }

    /**
     * @inheritDoc
     */
    public function getData(): ?array
    {
        $filteredLpa = $this->removeGhostActors($this->lpa->getData());
        $filteredLpa = $this->removeInactiveAttorneys($filteredLpa);

        return $filteredLpa;
    }

    /**
     * @inheritDoc
     */
    public function getLookupTime(): ?DateTime
    {
        return $this->lpa->getLookupTime();
    }

    /**
     * Returns an LPA data array with 'ghost' actors removed.
     *
     * Ghost actors are currently defined as having no firstname *and* no surname.
     *
     * @param array|null $lpaData
     * @return array|null
     */
    protected function removeGhostActors(?array $lpaData): ?array
    {
        if ($lpaData === null) {
            return $lpaData;
        }

        // --

        $lpaData['attorneys'] = array_filter($lpaData['attorneys'], function($attorney) {
            return !(empty($attorney['firstname']) && empty($attorney['surname']));
        });

        return $lpaData;
    }

    protected function removeInactiveAttorneys(?array $lpaData): ?array
    {
        if ($lpaData === null) {
            return $lpaData;
        }

        $lpaData['attorneys'] = array_filter($lpaData['attorneys'], function($attorney) {
            return ($attorney['systemStatus']);
        });

        return $lpaData;
    }


}