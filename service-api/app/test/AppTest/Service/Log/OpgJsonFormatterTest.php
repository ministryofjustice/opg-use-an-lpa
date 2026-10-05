<?php

declare(strict_types=1);

namespace AppTest\Service\Log;

use App\Service\Log\OpgJsonFormatter;
use App\Service\Log\OpgJsonFormatterFactory;
use App\Service\Log\RequestTracing;
use DateTimeImmutable;
use InvalidArgumentException;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpgJsonFormatterTest extends TestCase
{
    #[Test]
    public function it_outputs_the_required_fields_and_preserves_diagnostics(): void
    {
        $record            = $this->record();
        $record['context'] = [
            'event_code' => 'ACCOUNT_CREATED',
            'exception'  => new RuntimeException('Failed'),
        ];
        $record['extra']   = [
            RequestTracing::TRACE_PARAMETER_NAME => 'Root=1-581cf771-a006649127e371903a2de979',
            'file'                               => '/app/example.php',
            'line'                               => 42,
        ];

        $formatted = (new OpgJsonFormatter('api-app'))->format($record);
        $output    = json_decode($formatted, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('2024-02-14T13:39:01.123+00:00', $output['time']);
        self::assertSame('INFO', $output['level']);
        self::assertSame("A \"quoted\" message\nwith a newline", $output['msg']);
        self::assertSame('api-app', $output['service_name']);
        self::assertSame('Root=1-581cf771-a006649127e371903a2de979', $output['trace_id']);
        self::assertSame('ACCOUNT_CREATED', $output['context']['event_code']);
        self::assertSame('Failed', $output['context']['exception']['message']);
        self::assertSame('default', $output['channel']);
        self::assertArrayNotHasKey('datetime', $output);
        self::assertArrayNotHasKey('message', $output);
        self::assertArrayNotHasKey('level_name', $output);
        self::assertArrayNotHasKey('trace_id', $output['extra']);
        self::assertSame(1, substr_count($formatted, "\n"));
    }

    #[Test]
    public function it_omits_trace_id_when_none_is_available(): void
    {
        $record = $this->record();
        unset($record['extra'][RequestTracing::TRACE_PARAMETER_NAME]);

        $output = json_decode((new OpgJsonFormatter('api-app'))->format($record), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('trace_id', $output);
        self::assertSame($record['extra'], $output['extra']);
    }

    #[Test]
    public function it_requires_a_service_name_in_the_factory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new OpgJsonFormatterFactory())([]);
    }

    private function record(): array
    {
        return [
            'datetime'   => new DateTimeImmutable('2024-02-14T13:39:01.123456+00:00'),
            'level'      => Logger::INFO,
            'level_name' => 'INFO',
            'message'    => "A \"quoted\" message\nwith a newline",
            'channel'    => 'default',
            'context'    => [],
            'extra'      => [],
        ];
    }
}
