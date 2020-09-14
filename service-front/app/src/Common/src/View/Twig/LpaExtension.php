<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Form\Fieldset\Date;
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
    public function getFunctions(): array
    {
        return [
            new TwigFunction('actor_address', [$this, 'actorAddress']),
            new TwigFunction('actor_name', [$this, 'actorName']),
            new TwigFunction('lpa_date', [$this, 'lpaDate']),
            new TwigFunction('code_date', [$this, 'codeDate']),
            new TwigFunction('days_remaining_to_expiry', [$this, 'daysRemaining']),
            new TwigFunction('check_if_code_has_expired', [$this, 'hasCodeExpired']),
            new TwigFunction('add_hyphen_to_viewer_code', [$this, 'formatViewerCode']),
            new TwigFunction('check_if_code_is_cancelled', [$this, 'isCodeCancelled']),
            new TwigFunction('is_lpa_cancelled', [$this, 'isLpaCancelled']),
            ];
    }

    /**
     * @param CaseActor $actor
     * @return string
     */
    public function actorAddress(CaseActor $actor)
    {
        //  Multiple addresses can appear for an actor - just use the first one
        if (is_array($actor->getAddresses()) && count($actor->getAddresses()) > 0) {

            /** @var Address $address */
            $address = $actor->getAddresses()[0];

            return implode(', ', array_filter([
                $address->getAddressLine1(),
                $address->getAddressLine2(),
                $address->getAddressLine3(),
                $address->getTown(),
                $address->getCounty(),
                $address->getPostcode()
            ]));
        }

        return '';
    }

    /**
     * @param CaseActor $actor
     * @return string
     */
    public function actorName(CaseActor $actor, bool $withSalutation = true)
    {
        $nameData = [];

        if ($withSalutation) {
            $nameData[] = $actor->getSalutation();
        }

        $nameData[] = $actor->getFirstname();
        $nameData[] = $actor->getMiddlenames();
        $nameData[] = $actor->getSurname();

        return implode(' ', array_filter($nameData));
    }

    /**
     * Takes an input date, whether as a string (relative or absolute) or as a Datetime
     * and converts it for display in an LPA context.
     *
     * @param DateTime|string|null $date
     * @return string
     * @throws \Exception
     */
    public function lpaDate($date)
    {
        if (!is_null($date)) {
            if ($date === "today") {
                $date = new DateTime("today");
            } elseif (is_string($date)) {
                $date = DateTime::createFromFormat('Y-m-d', $date);
            }

            if ($date instanceof DateTime) {
                return $date->format('j F Y');
            }
        }

        return '';
    }

    /**
     * Takes an input date, whether as a string (relative or absolute) or as a Datetime
     * and converts it for displaying codes on check access codes page
     *
     * @param DateTime|string|null $date
     * @return string
     */
    public function codeDate($date)
    {

        if (!is_null($date)) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i:sP', $date);

            if ($date instanceof DateTime) {
                return $date->format('j F Y');
            } else {
                return '';
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
    public function daysRemaining(?string $expiryDate): string
    {
        $difference = '';

        if (!empty($expiryDate)) {
            $expires = new DateTime($expiryDate);
            $now = new DateTime("now");
            $difference = $expires->diff($now)->format('%a');
        }

        return $difference;
    }

    /**
     * Checks whether the code has been cancelled
     *
     * @param string|null $expiryDate
     * @return bool|null
     * @throws \Exception
     */
    public function isCodeCancelled(?array $code): ?bool
    {
        if (array_key_exists("Cancelled", $code)) {
            return $cancelledStatus = true;
        }

        return null;
    }

    /**
     * Checks whether the code has expired or not
     *
     * @param string|null $expiryDate
     * @return bool|null
     * @throws \Exception
     */
    public function hasCodeExpired(?string $expiryDate): ?bool
    {
        if (!empty($expiryDate && $date = new DateTime($expiryDate))) {
            return $date <= (new DateTime('now'))->setTime(23, 59, 59);
        }

        return null;
    }

    /**
     * Create a hyphenated viewer code
     *
     * @param string $viewerCode
     * @return string
     */
    public function formatViewerCode(?string $viewerCode): string
    {
        $viewerCodeParts = str_split($viewerCode, 4);
        array_unshift($viewerCodeParts, 'V');

        return implode(" - ", $viewerCodeParts);
    }

    /**
     * @param array $lpa
     * @return bool
     */
    public function isLPACancelled(Lpa $lpa): bool
    {
        $status = $lpa->getStatus();
        return ($status === 'Cancelled') || ($status === 'Revoked');
    }
}
