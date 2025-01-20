<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Common\Entity\CaseActor;
use Common\Entity\CombinedLpa;
use Common\Entity\Lpa;
use Common\Entity\Person;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\Factory\LpaDataFormatter;
use Common\Service\Lpa\Factory\PersonDataFormatter;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Exception;
use RuntimeException;

/**
 * Single action invokeable class that transforms incoming LPA data arrays from the API into ones containing
 * value objects and sane values.
 */
class ParseLpaData
{
    /**
     * @codeCoverageIgnore
     */
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
     *
     * @param  array{
     *     lpa: array,
     *     actor?: array,
     *     iap?: array,
     *     ...} $data
     * @return ArrayObject
     * @throws Exception
     */
    public function __invoke(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch ($dataItemName) {
                case 'lpa':
                    $data['lpa'] = $this->getLpa($dataItem);
                    break;
                case 'actor':
                    $data['actor'] = is_array($data['actor'])
                        ? $this->getActor($dataItem)
                        : $data['actor'];
                    break;
                case 'iap':
                    $data['iap'] = $this->imagesFactory->createFromData($dataItem);
                    break;
                default:
                    if (is_array($dataItem)) {
                        $data[$dataItemName] = ($this)($dataItem);
                    }
            }
        }

        return new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @param array $details
     * @return array{
     *     type: string,
     *     details: Person|CaseActor,
     * }
     * @throws UnableToHydrateObject
     * @throws RuntimeException
     */
    public function getActor(array $details): array
    {
        if (($this->featureEnabled)('support_datastore_lpas')) {
            $details['details'] = ($this->personDataFormatter)($details['details']);
        } else {
            $details['details'] = $this->lpaFactory->createCaseActorFromData($details['details']);
        }

        return $details;
    }

    /**
     * @param mixed $dataItem
     * @return array|Lpa
     * @throws UnableToHydrateObject
     * @throws RuntimeException
     */
    public function getLpa(array $dataItem): Lpa|CombinedLpa
    {
        if (($this->featureEnabled)('support_datastore_lpas')) {
            return ($this->lpaDataFormatter)($dataItem);
        } else {
            return $this->lpaFactory->createLpaFromData($dataItem);
        }
    }
}
