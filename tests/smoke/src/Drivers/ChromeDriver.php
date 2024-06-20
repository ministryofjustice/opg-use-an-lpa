<?php

declare(strict_types=1);

namespace Smoke\Drivers;

use Symfony\Component\Process\Process;

class ChromeDriver implements Driver
{
    private ?Process $process;

    public function __construct(
        bool $allowInsecureHttps = false,
        private string $binPath = '/usr/bin/google-chrome-stable',
        private array $binArguments = [
            '--disable-gpu',
            '--headless=new',
            '--disable-extensions',
            '--remote-debugging-port=9222',
            '--disable-setuid-sandbox',
            '--no-sandbox',
            '--window-size="1920,1080"',
            '--disable-dev-shm-usage',
            '--no-startup-window',
            '--no-first-run',
            '--no-pings',
        ],
    ) {
        $this->process = null;

        if ($allowInsecureHttps) {
            $this->binArguments[] = '--ignore-certificate-errors';
        }
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
        $this->process->setTimeout(120);
        $this->process->start();

        $this->process->waitUntil(
            function (string $type, string $output) {
                if (!($running = stristr($output, 'DevTools listening on ws://127.0.0.1:9222/devtools/browser/'))) {
                    echo $output;
                }
                return $running;
            }
        );
    }

    public function stop(): void
    {
        $this->process?->stop();
    }

    public function isRunning(): bool
    {
        if ($this->process === null) {
            return false;
        }

        return $this->process->isRunning();
    }
}
