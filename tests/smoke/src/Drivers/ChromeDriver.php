<?php

declare(strict_types=1);

namespace Smoke\Drivers;

use Symfony\Component\Process\Process;

class ChromeDriver implements Driver
{
    private array $binArguments;

    private string $binPath;

    private ?Process $process;

    public function __construct(
        string $binPath = '/usr/bin/google-chrome-stable',
        array $binArguments = [
            '--disable-gpu',
            '--headless',
            '--remote-debugging-address=0.0.0.0',
            '--disable-extensions',
            '--remote-debugging-port=9222',
            '--disable-setuid-sandbox',
            '--no-sandbox',
            '--window-size="1920,1080"',
            '--disable-dev-shm-usage',
            '--no-startup-window',
            '--no-first-run',
            '--no-pings'
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
                array_merge([$this->binPath], $this->binArguments)
            )
        );
        $this->process->start();

        $this->process->waitUntil(function ($type, $output) {
            if (! ($running = stristr($output, 'DevTools listening on ws://0.0.0.0:9222/devtools/browser/'))) {
                echo $output;
            }
            return $running;
        });
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