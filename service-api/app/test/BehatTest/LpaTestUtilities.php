<?php

declare(strict_types=1);

namespace BehatTest;

use stdClass;

class LpaTestUtilities
{
    /**
     * Iterates recursively through an Lpa stdClass object (e.g. as returned out of a json_decode) and transforms
     * Sirius style UIDs by removing the hyphens.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function SanitiseSiriusLpaUIds(stdClass $data): stdClass
    {
        foreach ($data as $key => &$value) {
            if ($key === 'uId') {
                $value = str_replace('-', '', $value);
            } elseif ($value instanceof stdClass) {
                $value = self::SanitiseSiriusLpaUIds($value);
            } elseif (is_array($value)) {
                $sanitisedItems = [];
                foreach ($value as $item) {
                    $sanitisedItems[] = self::SanitiseSiriusLpaUIds($item);
                }
                $value = $sanitisedItems;
            }
        }

        return $data;
    }
}
