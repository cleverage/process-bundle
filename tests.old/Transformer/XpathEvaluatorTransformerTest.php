<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Test the xpath_evaluator transformer.
 */
class XpathEvaluatorTransformerTest extends AbstractProcessTest
{
    public function testSimpleQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a>ok</a>');
        $this->assertTransformation('xpath_evaluator', 'ok', $domDocument, [
            'query' => '/a/text()',
        ]);
    }

    public function testAttributeValueQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<node data="ok">ko</node>');
        $this->assertTransformation('xpath_evaluator', 'ok', $domDocument, [
            'query' => '/node/@data',
        ]);
    }

    public function testSubQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];
        $this->assertTransformation('xpath_evaluator', 'ok', $node, [
            'query' => './c/text()',
        ]);
    }

    public function testMultiResults(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];
        $this->assertTransformation('xpath_evaluator', ['ok1', 'ok2', 'ok3'], $node, [
            'query' => './c/text()',
            'single_result' => false,
        ]);
    }

    public function testMultiResultsAsNodeList(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];
        /** @var \DOMNodeList $result */
        $result = $this->transform('xpath_evaluator', $node, [
            'query' => './c/text()',
            'single_result' => false,
            'unwrap_value' => false,
        ]);

        self::assertCount(3, $result);
        self::assertEquals('ok1', $result[0]->textContent);
        self::assertEquals('ok2', $result[1]->textContent);
        self::assertEquals('ok3', $result[2]->textContent);
    }

    public function testMultiQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><d>ok2</d><e>ok3</e></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];
        $this->assertTransformation('xpath_evaluator', ['ok1', 'ok2', 'ok3'], $node, [
            'query' => ['./c/text()', './d/text()', './e/text()'],
        ]);
    }

    public function testMultiQueryWithKey(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><d>ok2</d><e>ok3</e></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];
        $this->assertTransformation('xpath_evaluator', [
            'c' => 'ok1',
            'd' => 'ok2',
            'e' => 'ok3',
        ], $node, [
            'query' => [
                'c' => './c/text()',
                'd' => './d/text()',
                'e' => './e/text()',
            ],
        ]);
    }

    public function testOverridableSubqueries(): void
    {
        $xml = <<<XML

<a>
    <b>
        <c>ok1</c>
        <c>ok2</c>
        <c>ok3</c>
    </b>
    <d>
        <e>ok4</e>
        <f>ok5</f>
        <g>ok6</g>
    </d>
</a>
XML;
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        $node = $domDocument->getElementsByTagName('b')[0];
        $this->assertTransformation('xpath_evaluator', [
            'all_c_values' => ['ok1', 'ok2', 'ok3'],
            'e_value' => 'ok4',
            'f_value' => 'ok5',
        ], $node, [
            'query' => [
                'all_c_values' => [
                    'subquery' => '/a/b/c/text()',
                    'single_result' => false,
                ],
                'e_value' => '/a/d/e/text()',
                'f_value' => [
                    'subquery' => '/a/d/f/text()',
                ],
            ],
        ]);
    }
}
