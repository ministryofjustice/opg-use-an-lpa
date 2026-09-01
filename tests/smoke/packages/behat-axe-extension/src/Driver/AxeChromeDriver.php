<?php

declare(strict_types=1);

namespace BehatAxeExtension\Driver;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use JetBrains\PhpStorm\Language;

class AxeChromeDriver extends DriverDecorator
{
    /** @var array<string, array> */
    private array $results;

    public function __construct(DriverInterface $driver)
    {
        parent::__construct($driver);

        $this->results = [];
    }

    /**
     * @return void
     * @throws DriverException
     * @throws UnsupportedDriverActionException
     */
    public function runAxeTest(): void
    {
        $url      = $this->getCurrentUrl();
        $urlParts = parse_url($url);

        $this->executeScript(
            'if(typeof axe === "undefined") {
                    let axeNode=document.createElement("script");axeNode.src="/javascript/axe.min.js";
                    document.body.appendChild(axeNode);
                }'
        );
        $this->wait(1000, 'typeof axe !== "undefined"');
        if ($this->evaluateScript('typeof axe !== "undefined"')) {
            $this->executeScript(
                'window.__axeResults = "undefined";
                    axe.run(
                        document,
                        {},
                        function (err, results) {
                            window.__axeResults = err
                                ? JSON.stringify({ error: err.message })
                                : JSON.stringify(results);
                        }
                    );'
            );
            print_r($this->driver->getConsoleMessages() ?? '');
            $this->wait(5000, 'window.__axeResults !== "undefined"');
            if ($this->evaluateScript('window.__axeResults !== "undefined"')) {
                $output = $this->evaluateScript('window.__axeResults;');

                $this->results[$urlParts['path']] = json_decode($output, true)['violations'];
            }
        }
    }

    /**
     * Returns the captured results. Clears the results for subsequent runs.
     */
    public function getAxeResults(): array
    {
        $results       = $this->results;
        $this->results = [];

        return $results;
    }

    public function visit(string $url)
    {
        parent::visit($url);

        $headers = $this->getResponseHeaders();
        foreach ($headers as $name => $value) {
            if ($name === strtolower('Content-Type') && strpos($value, 'text/html') !== false) {
                $this->runAxeTest();
            }
        }
    }

    public function click(
        #[Language('XPath')]
        string $xpath,
    ) {
        parent::click($xpath);

        $headers = $this->getResponseHeaders();
        foreach ($headers as $name => $value) {
            if ($name === strtolower('Content-Type') && strpos($value, 'text/html') !== false) {
                $this->runAxeTest();
            }
        }
    }
}
