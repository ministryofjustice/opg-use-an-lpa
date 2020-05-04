<?php

declare(strict_types=1);

namespace Smoke;

use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Smoke\Drivers\Driver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DriverSubscriber implements EventSubscriberInterface
{
    /** @var Driver[] */
    private array $drivers;

    public function __construct()
    {
        $this->drivers = [];
    }

    /**
     * Add application drivers to the subscriber so it can work with them when events are fired.
     *
     * @param Driver $driver
     */
    public function addDriver(Driver $driver)
    {
        $this->drivers[] = $driver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SuiteTested::BEFORE => 'startDrivers',
            ExerciseCompleted::AFTER => 'stopDrivers'
        ];
    }

    public function startDrivers(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->start();
        }
    }

    public function stopDrivers(): void
    {
        foreach (array_reverse($this->drivers) as $driver) {
            $driver->stop();
        }
    }
}