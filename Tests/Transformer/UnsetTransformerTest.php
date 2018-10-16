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
            'to_test'  => 2,
        ];
        $result = $this->processManager->execute('test.unset_transformer.simple', $input);
        self::assertEquals(['other' => 1, 'to_test' => 2], $result);
    }

    /**
     * Assert a few simple condition can trigger unset (or not)
     */
    public function testConditionalUnset()
    {
        $input = [
            'other'    => 1,
            'to_unset' => 1,
            'to_test'  => 2,
        ];

        // Should unset
        $result = $this->processManager->execute('test.unset_transformer.condition', $input);
        self::assertEquals(['other' => 1, 'to_test' => 2], $result);

        // No unset
        $input['to_test'] = 3;
        $result = $this->processManager->execute('test.unset_transformer.condition', $input);
        self::assertEquals(['other' => 1, 'to_unset' => 1, 'to_test' => 3], $result);

        // Checking null, no unset
        $result = $this->processManager->execute('test.unset_transformer.condition_null', $input);
        self::assertEquals(['other' => 1, 'to_unset' => 1, 'to_test' => 3], $result);

        // Should unset
        $input['to_test'] = null;
        $result = $this->processManager->execute('test.unset_transformer.condition_null', $input);
        self::assertEquals(['other' => 1, 'to_test' => null], $result);
    }

    /**
     * Assert the transformer detect wrong types
     *
     * @expectedException \RuntimeException
     */
    public function testWrongUnsetString()
    {
        $this->processManager->execute('test.unset_transformer.simple', 'not an array');
    }

    /**
     * Assert the transformer detect wrong values
     *
     * @expectedException \RuntimeException
     */
    public function testWrongUnsetMissingProperty()
    {
        $this->processManager->execute('test.unset_transformer.simple', ['no property found']);
    }

}
