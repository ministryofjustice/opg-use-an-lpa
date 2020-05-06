<?php

declare(strict_types=1);

namespace Test\Context;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;

/**
 * Class CommonContext
 *
 * @package BehatTest\Context
 *
 * @property array responseJson The json returned from a previous step
 */
class CommonContext implements Context
{
    use BaseContextTrait;

    /**
     * @Given I fetch the healthcheck endpoint
     */
    public function iFetchTheHealthcheckEndpoint(): void
    {
        $this->ui->visit('/healthcheck');
    }

    /**
     * @Then I see JSON output
     */
    public function iSeeJsonOutput(): void
    {
        $this->responseJson = $this->assertJsonResponse();
    }

    /**
     * @Then it contains a :key key\/value pair
     */
    public function itContainsAKeyValuePair(string $key): void
    {
        if (! array_key_exists($key, $this->responseJson)) {
            throw new ExpectationException(
                sprintf('Failed to find the key %s in the Json response', $key),
                $this->ui->getSession()
            );
        }
    }

}