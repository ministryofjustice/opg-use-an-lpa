<?php

declare(strict_types=1);

namespace Common\View\Twig;

use InvalidArgumentException;
use Locale;
use NumberFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

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

        return $this->getFormatter(Locale::getDefault())->format($number);
    }

    /**
     * The Welsh ordinal formatter is incorrect for our use so we supply a custom ruleset to build our
     * own for the 'cy' locale.
     *
     * @param string $locale
     * @return NumberFormatter An ordinal formatter for the supplied locale
     */
    protected function getFormatter(string $locale): NumberFormatter
    {
        if ($locale === 'cy') {
            $pattern = <<<EOT
%digits-ordinal:
1: =#,##0=af;
2: =#,##0=il;
3: =#,##0=ydd;
5: =#,##0=ed;
7: =#,##0=fed;
11: =#,##0=eg;
12: =#,##0=fed;
13: =#,##0=eg;
15: =#,##0=fed;
16: =#,##0=eg;
18: =#,##0=fed;
19: =#,##0=eg;
20: =#,##0=fed;
21: =#,##0=ain;
-x: −>%digits-ordinal>;
EOT;
            $formatter = NumberFormatter::create($locale, NumberFormatter::PATTERN_RULEBASED, $pattern);
        } else {
            $formatter = NumberFormatter::create($locale, NumberFormatter::ORDINAL);
        }

        return $formatter;
    }
}
