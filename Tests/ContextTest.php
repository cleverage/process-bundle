<?php
namespace CleverAge\ProcessBundle\Tests;


class ContextTest extends AbstractProcessTest
{

    /**
     * Assert a value can correctly by passed through context
     */
    public function testSimpleContext()
    {
        $result = $this->processManager->execute('test.context', null, 'ko', ['value' => 'ok']);

        self::assertEquals('ok', $result);
    }
}
