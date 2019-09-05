<?php

namespace AppTest\DataAccess\DynamoDb;

use Aws\Result;
use DateTime;

trait GenerateAwsResultTrait
{
    /**
     * Function to mock the AWS result so that calling a Dynamo instance isn't necessary
     *
     * General guidance is "Don't mock interfaces you don't control" but pending a big rewrite
     * of the DynamoDB layer this is going to have to do.
     *
     * Pushing an array into the function formatted how you'd expect an AWS response to
     * look gets you back something that behaves correctly for our current purposes.
     *
     * ```php
     * $result = createAWSResult([
     *   'Items' => [
     *     [
     *       'Email' => [
     *         'S' => 'test@example.com',
     *       ],
     *       'PasswordResetToken' => [
     *         'S' => 'resetTokenAABBCCDDEE',
     *       ],
     *     ]
     *   ]
     * ])
     * ```
     *
     * @param array $items
     * @return Result
     */
    private function createAWSResult(array $items = []): Result
    {
        // wrap our array in a basic iterator
        $iterator = new \ArrayIterator($items);

        // using PHPUnit's mock as opposed to Prophecy since Prophecy doesn't support
        // "return by reference" which is what `foreach` expects.
        $awsResult = $this->createMock(Result::class);

        $awsResult
            ->method('offsetExists')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetExists($index);
            }));

        $awsResult
            ->method('offsetGet')
            ->with($this->isType('string'))
            ->will($this->returnCallback(function($index) use ($iterator) {
                return $iterator->offsetGet($index);
            }));

        return $awsResult;
    }
}
