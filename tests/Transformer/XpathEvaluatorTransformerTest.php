<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer
 */
class XpathEvaluatorTransformerTest extends TestCase
{
    /**
     * @covers ::buildXpath
     * @covers ::query
     * @covers ::transform
     */
    public function testSimpleQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a>ok</a>');

        $transformer = new XpathEvaluatorTransformer();
        $xpath = $transformer->buildXpath($domDocument);

        $this->assertInstanceOf(\DOMXPath::class, $xpath);

        $options = ['query' => '/a/text()', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $queryResult = $transformer->query($xpath, '/a/text()', $domDocument, $options);
        $this->assertEquals('ok', $queryResult);

        $result = $transformer->transform($domDocument, $options);
        $this->assertEquals('ok', $result);
    }

    /**
     * @covers ::buildXpath
     * @covers ::query
     * @covers ::transform
     */
    public function testAttributeValueQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<node data="ok">ko</node>');

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => '/node/@data', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($domDocument, $options);
        $this->assertEquals('ok', $result);
    }

    /**
     * @covers ::buildXpath
     * @covers ::query
     * @covers ::transform
     */
    public function testSubQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok</c></b></a>');
        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($node, $options);
        $this->assertEquals('ok', $result);
    }

    /**
     * @covers ::buildXpath
     * @covers ::query
     * @covers ::transform
     */
    public function testMultiResults(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => false, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($node, $options);
        $this->assertEquals(['ok1', 'ok2', 'ok3'], $result);
    }

    /**
     * @covers ::buildXpath
     * @covers ::query
     * @covers ::transform
     */
    public function testMultiResultsAsNodeList(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => false, 'ignore_missing' => true, 'unwrap_value' => false];

        $result = $transformer->transform($node, $options);

        self::assertCount(3, $result);
        self::assertEquals('ok1', $result[0]->textContent);
        self::assertEquals('ok2', $result[1]->textContent);
        self::assertEquals('ok3', $result[2]->textContent);
    }

    /**
     * @covers ::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new XpathEvaluatorTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('xpath_evaluator', $code);
    }
}
