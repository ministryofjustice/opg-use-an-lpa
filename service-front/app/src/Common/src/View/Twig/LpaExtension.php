<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Common\Entity\Person;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Entity\CombinedLpa;
use Common\Enum\Channel;
use DateTime;
use DateTimeInterface;
use Exception;
use IntlDateFormatter;
use Locale;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LpaExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('actor_address', [$this, 'actorAddress']),
            new TwigFunction('actor_name', [$this, 'actorName']),
            new TwigFunction('lpa_date', [$this, 'lpaDate']),
            new TwigFunction('code_date', [$this, 'formatDate']),
            new TwigFunction('days_remaining_to_expiry', [$this, 'daysRemaining']),
            new TwigFunction('check_if_code_has_expired', [$this, 'hasCodeExpired']),
            new TwigFunction('add_hyphen_to_viewer_code', [$this, 'formatViewerCode']),
            new TwigFunction('check_if_code_is_cancelled', [$this, 'isCodeCancelled']),
            new TwigFunction('is_lpa_cancelled', [$this, 'isLpaCancelled']),
            new TwigFunction('donor_name_with_dob_removed', [$this, 'donorNameWithDobRemoved']),
            new TwigFunction('is_donor_signature_date_too_old', [$this, 'isDonorSignatureDateOld']),
            new TwigFunction('is_sirius_lpa', [$this, 'isSiriusLpa']),
            new TwigFunction('is_online_channel', [$this, 'isOnlineChannel']),
        ];
    }

    public function actorAddress(CaseActor|Person $actor): string
    {
        // Multiple addresses can appear for an actor - just use the first one
        if ($actor instanceof CaseActor && $actor->getAddresses() > 0) {
            $addressObj = $actor->getAddresses()[0];
        } elseif ($actor instanceof Person) {
            $addressObj = $actor; // combined Person's have address data flattened
        } else {
            return '';
        }

        return implode(
            ', ',
            array_filter(
                [
                    $addressObj->getAddressLine1(),
                    $addressObj->getAddressLine2(),
                    $addressObj->getAddressLine3(),
                    $addressObj->getTown(),
                    $addressObj->getCounty(),
                    $addressObj->getPostcode(),
                    $addressObj->getCountry(),
                ]
            )
        );
    }

    /**
     * Removes the dob from the string and returns just donor name
     *
     * @param  string $donorNameAndDob
     * @return string
     */
    public function donorNameWithDobRemoved(string $donorNameAndDob): string
    {
        preg_match('/((\D*)(\d+[-]\d+[-]\d+))/', $donorNameAndDob, $matches);
        return trim($matches[2]);
    }

    /**
     * @param  CaseActor|Person $actor
     * @param  bool             $withSalutation Prepend salutation?
     * @return string
     */
    public function actorName(CaseActor|Person $actor, bool $withSalutation = true): string
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
     * Takes an input date, whether as a string (relative or absolute - in the format 2020-11-27)
     * or as a Datetime and converts it for displaying on pages
     *
     * @param  DateTimeInterface|string|null $date
     * @return string
     */
    public function lpaDate(DateTimeInterface|string|null $date): string
    {
        return $this->formatDate($date, 'Y-m-d');
    }

    /**
     * Takes an input date, whether as a string (relative or absolute) or as a Datetime
     * and converts it for displaying on pages
     *
     * @param  DateTimeInterface|string|null $date
     * @param  string                        $parseFormat A PHP Datetime format string that should be used to parse $date
     * @return string
     */
    public function formatDate(DateTimeInterface|string|null $date, string $parseFormat = 'Y-m-d\TH:i:sP'): string
    {
        if (!is_null($date)) {
            if ($date === 'today') {
                $date = new DateTime('today');
            } elseif (is_string($date)) {
                $date = DateTime::createFromFormat($parseFormat, $date);
            }

            if ($date instanceof DateTimeInterface) {
                $formatter = $this->getDateFormatter(Locale::getDefault());
                $formatter->setTimeZone($date->getTimezone());
                return $formatter->format($date);
            }
        }

        return '';
    }

    /**
     * Calculates the days remaining until the viewer code expires
     *
     * @param  string|null $expiryDate
     * @return string
     * @throws Exception
     */
    public function daysRemaining(?string $expiryDate): string
    {
        $difference = '';

        if (!empty($expiryDate)) {
            $expires    = new DateTime($expiryDate);
            $now        = new DateTime('now');
            $difference = $expires->diff($now)->format('%a');
        }

        return $difference;
    }

    /**
     * Checks whether the code has been cancelled
     *
     * @param  array $code
     * @return bool|null
     */
    public function isCodeCancelled(array $code): ?bool
    {
        if (array_key_exists('Cancelled', $code)) {
            return true;
        }

        return null;
    }

    /**
     * Checks whether the code has expired or not
     *
     * @param  string|null $expiryDate
     * @return bool|null
     * @throws Exception
     */
    public function hasCodeExpired(?string $expiryDate): ?bool
    {
        if ($expiryDate) {
            $date = new DateTime($expiryDate);
            return $date <= (new DateTime('now'))->setTime(23, 59, 59);
        }

        return null;
    }

    /**
     * Create a hyphenated viewer code
     *
     * @param  string $viewerCode
     * @return string
     */
    public function formatViewerCode(string $viewerCode): string
    {
        $viewerCodeParts = str_split($viewerCode, 4);
        array_unshift($viewerCodeParts, 'V');

        return implode(' - ', $viewerCodeParts);
    }

    public function isLPACancelled(Lpa|CombinedLpa $lpa): bool
    {
        $status = $lpa->getStatus();
        return ($status === 'Cancelled') || ($status === 'Revoked');
    }

    public function isDonorSignatureDateOld(Lpa|CombinedLpa $lpa): bool
    {
        return $lpa->getLpaDonorSignatureDate() < new DateTime('2016-01-01');
    }

    public function isSiriusLpa(string $lpaUid): bool
    {
        return !str_starts_with($lpaUid, 'M-');
    }

    /**
     * Creates an international date formatter that is capable of doing locale based dates.
     *
     * @param  string $locale
     * @return IntlDateFormatter
     */
    private function getDateFormatter(string $locale): IntlDateFormatter
    {
        return IntlDateFormatter::create(
            $locale,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Europe/London',
            IntlDateFormatter::GREGORIAN
        );
    }

    public function isOnlineChannel(Lpa|CombinedLpa $lpa): bool
    {
        return $lpa->getChannel() === Channel::ONLINE;
    }
}
