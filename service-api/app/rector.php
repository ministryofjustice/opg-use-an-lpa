<?php

/**
 * Rector script file to fix up parts of the code base as and when needed.
 *
 * To run:
 * composer run dc-php -- vendor/bin/rector
 */

declare(strict_types=1);

use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;

///**
// * Test Upgrades
// */
//
//return RectorConfig::configure()
//    ->withPaths(
//        [
//            __DIR__ . '/test',
//        ]
//    )
//    ->withRules(
//        [
//            AddVoidReturnTypeWhereNoReturnRector::class,
//            CoversAnnotationWithValueToAttributeRector::class,
//            DataProviderAnnotationToAttributeRector::class,
//            GetMockBuilderGetMockToCreateMockRector::class,
//        ]
//    )
//    ->withConfiguredRule(
//        AnnotationToAttributeRector::class,
//        [
//            new AnnotationToAttribute('test', Test::class),
//        ],
//    )
//    ->withImportNames(importShortClasses: false);

/**
 * Behat Upgrades
 */

return RectorConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/features/context',
        ]
    )
    ->withPreparedSets(
        deadCode:         true,
        codeQuality:      true,
        codingStyle:      true,
        typeDeclarations: true,
        privatization:    true,
        instanceOf:       true,
        earlyReturn:      true,
        strictBooleans:   true,
    )
    ->withAttributesSets()
    ->withPhpSets()
    ->withImportNames(removeUnusedImports: true)
    ->withSkip(
        [
            NewlineAfterStatementRector::class,
        ]
    );
