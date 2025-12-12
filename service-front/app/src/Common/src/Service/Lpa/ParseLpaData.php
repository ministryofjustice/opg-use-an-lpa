<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Entity\{CaseActor, CombinedLpa, Lpa as SiriusLpa, Person};
use Common\Exception\LpaRecordInErrorException;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\Factory\{LpaDataFormatter, PersonDataFormatter};
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use RuntimeException;

/**
 * Single action invokeable class that transforms incoming LPA data arrays from the API into ones containing
 * value objects and sane values.
 */
class ParseLpaData
{
    public function __construct(
        private LpaFactory $lpaFactory,
        private InstAndPrefImagesFactory $imagesFactory,
        private LpaDataFormatter $lpaDataFormatter,
        private PersonDataFormatter $personDataFormatter,
        private FeatureEnabled $featureEnabled,
    ) {
    }

    /**
     * Attempts to convert the data arrays received via the various endpoints into an ArrayObject containing
     * scalar and object values.
     *
     * Currently, fairly naive in its assumption that the data types are stored under explicit keys, which
     * may change.
     */
    public function __invoke(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch ($dataItemName) {
                case 'lpa':
                    $data['lpa'] = $this->getLpa($dataItem);
                    break;
                case 'actor':
                    $data['actor'] = is_array($dataItem)
                        ? $this->getActor($dataItem)
                        : $data['actor'];
                    break;
                case 'iap':
                    $data['iap'] = $this->imagesFactory->createFromData($dataItem);
                    break;
                case 'error':
                    throw new LpaRecordInErrorException($data['error']);
                default:
                    if (is_array($dataItem)) {
                        try {
                            $data[$dataItemName] = ($this)($dataItem);
                        } catch (LpaRecordInErrorException) {
                            // TODO potentially we'd want to still allow this record through but show the user
                            //      that this particular lpa wasn't able to be loaded.
                            unset($data[$dataItemName]);
                        }
                    }
            }
        }

        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @param array{
     *      type: string,
     *      details: array,
     *  } $details
     * @return array{
     *     type: string,
     *     details: Person|CaseActor,
     * }
     * @throws UnableToHydrateObject
     * @throws RuntimeException
     */
    private function getActor(array $details): array
    {
        if (($this->featureEnabled)('support_datastore_lpas')) {
            $details['details'] = ($this->personDataFormatter)($details['details']);
        } else {
            $details['details'] = $this->lpaFactory->createCaseActorFromData($details['details']);
        }

        return $details;
    }

    /**
     * @param array $dataItem
     * @return SiriusLpa|CombinedLpa
     * @throws UnableToHydrateObject
     * @throws RuntimeException
     */
    private function getLpa(array $dataItem): SiriusLpa|CombinedLpa
    {
        if (($this->featureEnabled)('support_datastore_lpas')) {
            return ($this->lpaDataFormatter)($dataItem);
        } else {
            return $this->lpaFactory->createLpaFromData($dataItem);
        }
    }
}
