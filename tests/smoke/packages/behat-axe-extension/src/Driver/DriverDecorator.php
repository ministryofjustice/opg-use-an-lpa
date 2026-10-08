<?php

declare(strict_types=1);

namespace BehatAxeExtension\Driver;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Session;
use JetBrains\PhpStorm\Language;

/**
 * Decorates a Mink DriverInterface, forwarding every call to the wrapped driver.
 * Intended to be extended by decorators that need to intercept specific behaviour.
 */
class DriverDecorator implements DriverInterface
{
    public function __construct(protected readonly DriverInterface $driver)
    {
    }

    public function setSession(Session $session)
    {
        return $this->driver->setSession($session);
    }

    public function start()
    {
        return $this->driver->start();
    }

    public function isStarted()
    {
        return $this->driver->isStarted();
    }

    public function stop()
    {
        return $this->driver->stop();
    }

    public function reset()
    {
        return $this->driver->reset();
    }

    public function visit(string $url)
    {
        return $this->driver->visit($url);
    }

    public function getCurrentUrl()
    {
        return $this->driver->getCurrentUrl();
    }

    public function reload()
    {
        return $this->driver->reload();
    }

    public function forward()
    {
        return $this->driver->forward();
    }

    public function back()
    {
        return $this->driver->back();
    }

    public function setBasicAuth($user, string $password)
    {
        return $this->driver->setBasicAuth($user, $password);
    }

    public function switchToWindow(?string $name = null)
    {
        return $this->driver->switchToWindow($name);
    }

    public function switchToIFrame(?string $name = null)
    {
        return $this->driver->switchToIFrame($name);
    }

    public function setRequestHeader(string $name, string $value)
    {
        return $this->driver->setRequestHeader($name, $value);
    }

    public function getResponseHeaders()
    {
        return $this->driver->getResponseHeaders();
    }

    public function setCookie(string $name, ?string $value = null)
    {
        return $this->driver->setCookie($name, $value);
    }

    public function getCookie(string $name)
    {
        return $this->driver->getCookie($name);
    }

    /**
     * A ChromeDriver specific method to get all cookies.
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->driver->getCookies();
    }

    public function getStatusCode()
    {
        return $this->driver->getStatusCode();
    }

    public function getContent()
    {
        return $this->driver->getContent();
    }

    public function getScreenshot()
    {
        return $this->driver->getScreenshot();
    }

    public function getWindowNames()
    {
        return $this->driver->getWindowNames();
    }

    public function getWindowName()
    {
        return $this->driver->getWindowName();
    }

    public function find(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->find($xpath);
    }

    public function getTagName(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->getTagName($xpath);
    }

    public function getText(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->getText($xpath);
    }

    public function getHtml(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->getHtml($xpath);
    }

    public function getOuterHtml(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->getOuterHtml($xpath);
    }

    public function getAttribute(
        #[Language('XPath')]
        string $xpath,
        string $name,
    ) {
        return $this->driver->getAttribute($xpath, $name);
    }

    public function getValue(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->getValue($xpath);
    }

    public function setValue(
        #[Language('XPath')]
        string $xpath,
        $value,
    ) {
        return $this->driver->setValue($xpath, $value);
    }

    public function check(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->check($xpath);
    }

    public function uncheck(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->uncheck($xpath);
    }

    public function isChecked(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->isChecked($xpath);
    }

    public function selectOption(
        #[Language('XPath')]
        string $xpath,
        string $value,
        bool $multiple = false,
    ) {
        return $this->driver->selectOption($xpath, $value, $multiple);
    }

    public function isSelected(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->isSelected($xpath);
    }

    public function click(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->click($xpath);
    }

    public function doubleClick(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->doubleClick($xpath);
    }

    public function rightClick(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->rightClick($xpath);
    }

    public function attachFile(
        #[Language('XPath')]
        string $xpath,
        #[Language('file-reference')]
        string $path,
    ) {
        return $this->driver->attachFile($xpath, $path);
    }

    public function isVisible(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->isVisible($xpath);
    }

    public function mouseOver(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->mouseOver($xpath);
    }

    public function focus(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->focus($xpath);
    }

    public function blur(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->blur($xpath);
    }

    public function keyPress(
        #[Language('XPath')]
        string $xpath,
        $char,
        ?string $modifier = null,
    ) {
        return $this->driver->keyPress($xpath, $char, $modifier);
    }

    public function keyDown(
        #[Language('XPath')]
        string $xpath,
        $char,
        ?string $modifier = null,
    ) {
        return $this->driver->keyDown($xpath, $char, $modifier);
    }

    public function keyUp(
        #[Language('XPath')]
        string $xpath,
        $char,
        ?string $modifier = null,
    ) {
        return $this->driver->keyUp($xpath, $char, $modifier);
    }

    public function dragTo(
        #[Language('XPath')]
        string $sourceXpath,
        #[Language('XPath')]
        string $destinationXpath,
    ) {
        return $this->driver->dragTo($sourceXpath, $destinationXpath);
    }

    public function executeScript(
        #[Language('JavaScript')]
        string $script,
    ) {
        return $this->driver->executeScript($script);
    }

    public function evaluateScript(
        #[Language('JavaScript')]
        string $script,
    ) {
        return $this->driver->evaluateScript($script);
    }

    public function wait(
        int $timeout,
        #[Language('JavaScript')]
        string $condition,
    ) {
        return $this->driver->wait($timeout, $condition);
    }

    public function resizeWindow(int $width, int $height, ?string $name = null)
    {
        return $this->driver->resizeWindow($width, $height, $name);
    }

    public function maximizeWindow(?string $name = null)
    {
        return $this->driver->maximizeWindow($name);
    }

    public function submitForm(
        #[Language('XPath')]
        string $xpath,
    ) {
        return $this->driver->submitForm($xpath);
    }
}
