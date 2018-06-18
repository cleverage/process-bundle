<?php

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the MappingTransformer
 */
class MappingTransformerTest extends AbstractProcessTest
{

    /**
     * Assert a simple mapping transformation, from one array to another
     */
    public function testSimpleMapping()
    {
        $result = $this->processManager->execute('test.mapping_transformer.simple', null, ['field' => 'value']);

        self::assertEquals(['field2' => 'value'], $result);
    }

    /**
     * Assert that if "ignore_missing" is false, then an error is thrown for missing fields
     *
     * @expectedException \RuntimeException
     */
    public function testMissingMapping()
    {
        $this->processManager->execute('test.mapping_transformer.error', null, ['field' => 'value']);
    }

    /**
     * Assert we can use multiple times the same sub-transformer using # suffixes
     */
    public function testMultiSubtransformers()
    {
        $result = $this->processManager->execute('test.mapping_transformer.multi_subtransformers', null, ['field' => [3, null, 4, 2]]);

        self::assertEquals(['field2' => [2, 4, 3]], $result);
    }
}
