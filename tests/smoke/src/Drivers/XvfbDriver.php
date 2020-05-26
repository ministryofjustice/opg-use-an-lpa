<?php

declare(strict_types=1);

namespace Smoke\Drivers;

use Symfony\Component\Process\Process;

class XvfbDriver implements Driver
{
    /**
     * @var string[]
     */
    private array $binArguments;

    private string $binPath;

    private ?Process $process;

    public function __construct(
        string $binPath = '/usr/bin/Xvfb',
        array $binArguments = [
            ':99',
            '-screen',
            '0 1920x1080x24'
        ]
    ) {
        $this->binPath = $binPath;
        $this->binArguments = $binArguments;

        $this->process = null;
    }

    public function start(): void
    {
        if ($this->process !== null) {
            return;
        }

        $this->process = Process::fromShellCommandline(
            implode(
                ' ',
                array_merge(['sudo', $this->binPath], $this->binArguments)
            )
        );
        $this->process->start();
    }

    public function stop(): void
    {
        $this->process->stop();
    }

    public function isRunning(): bool
    {
        if ($this->process !== null) {
            return false;
        }

        return $this->process->isRunning();
    }
}
