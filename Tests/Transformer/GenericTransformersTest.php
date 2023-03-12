<?php

declare(strict_types=1);

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

class GenericTransformersTest extends AbstractProcessTest
{
    public function testSimple(): void
    {
        $this->assertTransformation('test.generic_transformers.simple', 'my_ok', 'my_ok');
        $this->assertTransformation('test.generic_transformers.simple', 'ok', null);
    }

    public function testContextualOptions(): void
    {
        $this->assertTransformation('test.generic_transformers.contextual_options', 'my_ok', 'my_ok', [
            'default_value' => 'ok',
        ]);
        $this->assertTransformation('test.generic_transformers.contextual_options', 'ok', null, [
            'default_value' => 'ok',
        ]);
    }
}
