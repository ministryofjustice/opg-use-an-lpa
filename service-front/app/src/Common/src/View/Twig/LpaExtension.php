<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use ArrayObject;
use DateTime;

/**
 * Class LpaExtension
 * @package Common\View\Twig
 */
class LpaExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFunctions() : array
    {
        return [
            new TwigFunction('actor_address', [$this, 'actorAddress']),
            new TwigFunction('actor_name', [$this, 'actorName']),
            new TwigFunction('lpa_date', [$this, 'lpaDate']),
        ];
    }

    /**
     * @param ArrayObject $actor
     * @return string
     */
    public function actorAddress(ArrayObject $actor)
    {
        //  Multiple addresses can appear for an actor - just use the first one
        if (isset($actor['addresses']) && !empty($actor['addresses'])) {
            $filteredAddress = $this->filterData($actor['addresses'][0], [
                'addressLine1',
                'addressLine2',
                'addressLine3',
                'town',
                'county',
                'postcode',
            ]);

            return implode(', ', $filteredAddress);
        }

        return '';
    }

    /**
     * @param ArrayObject $actor
     * @return string
     */
    public function actorName(ArrayObject $actor)
    {
        $filteredName = $this->filterData($actor, [
            'salutation',
            'firstname',
            'surname',
        ]);

        return implode(' ', $filteredName);
    }

    /**
     * Filter the data in to the fields provided and in the same order
     *
     * @param ArrayObject $data
     * @param array $filterFields
     * @return array
     */
    private function filterData(ArrayObject $data, array $filterFields)
    {
        $filteredData = [];

        foreach ($filterFields as $filterField) {
            if (array_key_exists($filterField, $data) && !empty($data[$filterField])) {
                $filteredData[] = $data[$filterField];
            }
        }

        return $filteredData;
    }

    /**
     * @param string|null $date
     * @param string $formatOut
     * @param string $formatIn
     * @return string
     */
    public function lpaDate(?string $date, $formatOut = 'j F Y', $formatIn = 'Y-m-d')
    {
        if (!empty($date)) {
            $date = DateTime::createFromFormat($formatIn, $date);

            if ($date instanceof DateTime) {
                return $date->format($formatOut);
            }
        }

        return '';
    }
}
