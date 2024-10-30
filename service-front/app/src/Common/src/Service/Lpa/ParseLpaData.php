<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Exception;
use Common\Service\Lpa\Factory\LpaDataFormatter;

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
    ) {
    }

    /**
     * Attempts to convert the data arrays received via the various endpoints into an ArrayObject containing
     * scalar and object values.
     *
     * Currently, fairly naive in its assumption that the data types are stored under explicit keys, which
     * may change.
     *
     * @param array{
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
                    //introduce feature flag here #3551
                    //the lpaData array converted to object using hydrator
                    if ($this->featureFlags['support_datastore_lpas'] ?? false) {
                        $data['lpa'] = ($this->lpaDataFormatter)($dataItem);
                    }
                    else {
                        $data['lpa'] = $this->lpaFactory->createLpaFromData($dataItem);
                    }
                    break;
                case 'actor':
                    $data['actor']['details'] = $this->lpaFactory->createCaseActorFromData($dataItem['details']);
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
}
