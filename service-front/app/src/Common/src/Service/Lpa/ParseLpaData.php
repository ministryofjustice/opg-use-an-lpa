<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Class ParseLpaData
 *
 * Single action invokeable class that transforms incoming LPA data arrays from the API into ones containing
 * value objects and sane values.
 *
 * @package Common\Service\Lpa
 */
class ParseLpaData
{
    /** @var LpaFactory */
    private LpaFactory $lpaFactory;

    public function __construct(LpaFactory $lpaFactory)
    {
        $this->lpaFactory = $lpaFactory;
    }

    /**
     * Attempts to convert the data arrays received via the various endpoints into an ArrayObject containing
     * scalar and object values.
     *
     * Currently fairly naive in its assumption that the data types are stored under explicit keys, which
     * may change.
     *
     * @param array $data
     *
     * @return ArrayObject
     * @throws \Exception
     */
    public function __invoke(array $data): ArrayObject
    {
        foreach ($data as $dataItemName => $dataItem) {
            switch ($dataItemName) {
                case 'lpa':
                    $data['lpa'] = $this->lpaFactory->createLpaFromData($dataItem);
                    break;
                case 'actor':
                    $data['actor']['details'] = $this->lpaFactory->createCaseActorFromData($dataItem['details']);
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
