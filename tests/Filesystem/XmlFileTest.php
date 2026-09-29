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

namespace CleverAge\ProcessBundle\Tests\Filesystem;

use CleverAge\ProcessBundle\Filesystem\XmlFile;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(XmlFile::class)]
class XmlFileTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xml_file_test_');
        self::assertIsString($path);
        $this->path = $path;
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testReadValidXml(): void
    {
        file_put_contents($this->path, '<root><a>1</a></root>');

        $dom = (new XmlFile($this->path))->read();

        self::assertNotNull($dom->documentElement);
        self::assertSame('root', $dom->documentElement->nodeName);
        self::assertSame('1', $dom->documentElement->textContent);
    }

    public function testReadEmptyFileThrows(): void
    {
        file_put_contents($this->path, '');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(\sprintf('XML file "%s" is empty', $this->path));

        (new XmlFile($this->path))->read();
    }

    public function testReadMalformedXmlThrowsWithLibxmlMessage(): void
    {
        file_put_contents($this->path, '<root><a>1</a>');

        try {
            (new XmlFile($this->path))->read();
            self::fail('An exception should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertStringContainsString(\sprintf('Invalid XML in file "%s"', $this->path), $e->getMessage());
            self::assertStringContainsString('Premature end of data in tag root', $e->getMessage());
        }
    }

    public function testReadNotXmlThrows(): void
    {
        file_put_contents($this->path, 'not xml at all');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Start tag expected, '<' not found");

        (new XmlFile($this->path))->read();
    }

    public function testReadUndefinedNamespacePrefixThrows(): void
    {
        file_put_contents($this->path, '<root><x:a/></root>');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Namespace prefix x on a is not defined');

        (new XmlFile($this->path))->read();
    }

    public function testReadRestoresLibxmlErrorHandling(): void
    {
        file_put_contents($this->path, '<root>');
        $previous = libxml_use_internal_errors(false);

        try {
            (new XmlFile($this->path))->read();
            self::fail('An exception should have been thrown');
        } catch (\UnexpectedValueException) {
            self::assertFalse(libxml_use_internal_errors());
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    public function testWriteThrowsWhenTheXmlCannotBeGenerated(): void
    {
        $dom = new class extends \DOMDocument {
            public function saveXML(?\DOMNode $node = null, int $options = 0): string|false
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not generate the XML content');

        (new XmlFile($this->path, 'wb'))->write($dom);
    }

    public function testWriteThenRead(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<root><a>ok</a></root>');

        (new XmlFile($this->path, 'wb'))->write($dom);
        $read = (new XmlFile($this->path))->read();

        self::assertSame('ok', $read->documentElement?->textContent);
    }
}
