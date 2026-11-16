<?php

declare(strict_types=1);

namespace Common\Service\Log;

use DateTimeInterface;
use Monolog\Formatter\JsonFormatter;

final class OpgJsonFormatter extends JsonFormatter
{
    public function __construct(private readonly string $serviceName)
    {
        parent::__construct(self::BATCH_MODE_NEWLINES);

        $this->setDateFormat(DateTimeInterface::RFC3339_EXTENDED);
    }

    public function format(array $record): string
    {
        $output = [
            'time'         => $record['datetime'],
            'level'        => $record['level_name'],
            'msg'          => $record['message'],
            'service_name' => $this->serviceName,
            'channel'      => $record['channel'],
            'context'      => $record['context'],
            'extra'        => $record['extra'],
        ];

        foreach (['trace_id', 'alb_trace_id'] as $field) {
            if (array_key_exists($field, $record['extra'])) {
                $output[$field] = $record['extra'][$field];
                unset($output['extra'][$field]);
            }
        }

        return parent::format($output);
    }
}
