<?php

declare(strict_types=1);

namespace Common\Service\I18n;

use IntlException;
use InvalidArgumentException;
use MessageFormatter;

/**
 * Class ICUMessageFormatter
 *
 * Wraps and implements the intl extension MessageFormatter class to provide ICU format string interpolation
 * and translation services.
 *
 * @package Common\Service\I18n
 */
class ICUMessageFormatter
{
    private array $cache = [];

    public function format(string $message, string $locale, array $arguments = []): string
    {
        // MessageFormatter constructor throws an exception if the message is empty
        if ('' === $message) {
            return '';
        }

        if (!$formatter = $this->cache[$locale][$message] ?? null) {
            try {
                $this->cache[$locale][$message] = $formatter = new MessageFormatter($locale, $message);
            } catch (IntlException $e) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid message format (error #%d): ',
                        intl_get_error_code()
                    ) . intl_get_error_message(),
                    0,
                    $e
                );
            }
        }

        // Cleans up argument key names
        foreach ($arguments as $key => $value) {
            if (\in_array($key[0] ?? null, ['%', '{'], true)) {
                unset($arguments[$key]);
                $arguments[trim($key, '%{ }')] = $value;
            }
        }

        if (false === $message = $formatter->format($arguments)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to format message (error #%s): ',
                    $formatter->getErrorCode()
                ) . $formatter->getErrorMessage()
            );
        }

        return $message;
    }
}
