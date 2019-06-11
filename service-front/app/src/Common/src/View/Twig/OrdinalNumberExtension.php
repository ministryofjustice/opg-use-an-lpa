<?php

declare(strict_types=1);

namespace Common\View\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use InvalidArgumentException;

/**
 * Class OrdinalNumberExtension
 * @package Common\View\Twig
 */
class OrdinalNumberExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('ordinal', [$this, 'makeOrdinal']),
        ];
    }

    /**
     * @param $number
     * @return string
     */
    public function makeOrdinal($number)
    {
        if (!is_int($number)) {
            throw new InvalidArgumentException('Ordinals can only be provided for integers');
        }

        $ord = 'th';

        if ($number < 11 || $number > 13) {
            switch ($number % 10) {
                case 1:
                    $ord = 'st';
                    break;
                case 2:
                    $ord = 'nd';
                    break;
                case 3:
                    $ord = 'rd';
                    break;
            }
        }

        return $number . $ord;
    }
}
