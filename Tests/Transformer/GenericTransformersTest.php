<?php


namespace CleverAge\ProcessBundle\Tests\Transformer;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class GenericTransformersTest extends AbstractProcessTest
{
    /**
     * @throws ExceptionInterface
     */
    public function testSimple()
    {
        $this->assertTransformation('test.generic_transformers.simple','my_ok', 'my_ok');
        $this->assertTransformation('test.generic_transformers.simple','ok', null);
    }
    /**
     * @throws ExceptionInterface
     */
    public function testContextualOptions()
    {
        $this->assertTransformation('test.generic_transformers.contextual_options','my_ok', 'my_ok', [
            'default_value' => 'ok'
        ]);
        $this->assertTransformation('test.generic_transformers.contextual_options','ok', null, [
            'default_value' => 'ok'
        ]);
    }

}
