<?php

declare(strict_types=1);

namespace AppTest\Service\ViewerCodes;

use PHPUnit\Framework\TestCase;
use App\Service\ViewerCodes\CodeGenerator;

class CodeGeneratorTest extends TestCase
{
    public function test_constants()
    {
        /*
         * The combination of these 2 values makes up the entropy in the code.
         * They should therefore only be changed with caution and consideration.
         * Thus them appearing here.
         */
        $this->assertEquals(12, CodeGenerator::CODE_LENGTH);
        $this->assertEquals('346789BCDFGHJKMPQRTVWXY', CodeGenerator::ALLOWED_CHARACTERS);
    }

    /**
     * WARNING: This test is slow; but it's worth the wait!
     */
    public function test_code_characteristics()
    {
        $allowedCharArray = str_split(CodeGenerator::ALLOWED_CHARACTERS);

        //---

        // We are going to count the number of times each character is seen, thus we setup an array to do that in.
        $characterCount = [];
        foreach ($allowedCharArray as $char) {
            $characterCount[$char] = 0;
        }

        //---

        for ($i = 0; $i < 50000; $i++) {    // We need a large data sample for the last test, thus 50,000.

            $code = CodeGenerator::generateCode();

            //---

            // All should be 12 characters
            $this->assertEquals(CodeGenerator::CODE_LENGTH, strlen($code));

            //---

            $usedCharsArray = str_split($code);
            $diff = array_diff($usedCharsArray, $allowedCharArray);

            // This should always be 0.
            // i.e. A code should never contain a character that's not in CodeGenerator::ALLOWED_CHARACTERS
            $this->assertCount(0, $diff);

            //---

            // Record each character used
            foreach ($usedCharsArray as $char) {
                $characterCount[$char]++;
            }
        }

        //---

        /*
         * The following test works by looking at the difference between the least and most frequent code character.
         * Given a large enough data set, the difference between the two frequencies should converge on 1.0
         */

        asort($characterCount);

        $lowestFrequency = array_shift($characterCount);
        $highestFrequency = array_pop($characterCount);

        $ratio = $highestFrequency / $lowestFrequency;

        // We want teh value to be 1.0, within 1 decimal place.
        // e.g. 1.03 should pass. 1.09 should not.
        $this->assertEquals(1, round($ratio, 1),
            'There is an element of chance that this test might fail. Try re-running if nothing has changed.'
        );
    }
}
