<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use PHPUnit\Framework\Assert;

/**
 * @property $traceId The X-Amzn-Trace-Id that gets attached to incoming requests by the AWS LB
 */
class CommonContext implements Context
{
    use BaseAcceptanceContextTrait;

    /**
     * @Given /^I attach a tracing header to my requests$/
     */
    public function iAttachATracingHeaderToMyRequests(): void
    {
        $this->traceId = 'Root=1-1-11';

        $this->ui->getSession()->setRequestHeader('X-Amzn-Trace-Id', $this->traceId);
    }

    /**
     * @Then /^my outbound requests have attached tracing headers$/
     *
     * Relies on a previous context steps having set the last request value using
     * {@link BaseUiContextTrait::setLastRequest()}
     */
    public function myOutboundRequestsHaveAttachedTracingHeaders(): void
    {
        $request = $this->getLastRequest();

        Assert::assertTrue($request->hasHeader(strtolower('X-Amzn-Trace-Id')));
        Assert::assertContains($this->traceId, $request->getHeader(strtolower('X-Amzn-Trace-Id')));
    }
}
