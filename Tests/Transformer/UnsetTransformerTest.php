<?php

namespace CleverAge\ProcessBundle\Tests\Transformer;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the UnsetTransformer
 */
class UnsetTransformerTest extends AbstractProcessTest
{
    /**
     * Assert the transformer can do a simple unset
     */
    public function testSimpleUnset()
    {
        $input = [
            'other'    => 1,
            'to_unset' => 1,
        ];
        $result = $this->processManager->execute('test.unset_transformer.simple', null, $input);
        self::assertEquals(['other' => 1], $result);
    }

}
