<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
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
            new TwigFunction('days_remaining_to_expiry', [$this, 'daysRemaining']),
        ];
    }

    /**
     * @param iterable $actor
     * @return string
     */
    public function actorAddress(iterable $actor)
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
     * @param iterable $actor
     * @return string
     */
    public function actorName(iterable $actor)
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
     * @param iterable $data
     * @param array $filterFields
     * @return array
     */
    private function filterData(iterable $data, array $filterFields)
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
     * @return string
     */
    public function lpaDate(?string $date)
    {
        if (!empty($date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date);

            if ($date instanceof DateTime) {
                return $date->format('j F Y');
            }
        }

        return '';
    }

    /**
     * Calculates the days remaining until the viewer code expires
     *
     * @param string $expiryDate
     * @return string
     * @throws \Exception
     */
    public function daysRemaining(?string $expiryDate) : string
    {
        $difference = '';

        if (!empty($expiryDate)) {
            $expires = new DateTime($expiryDate);
            $now = new DateTime("now");
            $difference = $expires->diff($now)->format('%a');
        }

        return $difference;
    }

}
