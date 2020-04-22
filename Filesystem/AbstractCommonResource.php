<?php declare(strict_types=1);

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Common methods for readers using resources
 */
abstract class AbstractCommonResource implements FileStreamInterface
{
    /** @var resource */
    protected $handler;

    /** @var int|null */
    protected $lineCount;

    /** @var int */
    protected $lineNumber = 1;

    /** @var bool */
    protected $closed;

    /** @var bool */
    protected $seekCalled = false;

    /**
     * @param resource $resource
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($resource)
    {
        if (!\is_resource($resource)) {
            $type = \gettype($resource);
            throw new \UnexpectedValueException("Resource argument must be a resource, '{$type}' given");
        }
        $this->handler = $resource;
    }

    /**
     * @return resource
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Warning! This method will rewind the file to the beginning before and after counting the lines!
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function getLineCount(): int
    {
        if (null === $this->lineCount) {
            $this->rewind();
            $line = 0;
            while (!$this->isEndOfFile()) {
                ++$line;
                $this->readRaw();
            }
            $this->rewind();

            $this->lineCount = $line;
        }

        return $this->lineCount;
    }

    /**
     * {@inheritDoc}
     */
    public function getLineNumber(): int
    {
        if ($this->seekCalled) {
            throw new \LogicException('Cannot get current line number after calling "seek": the line number is lost');
        }

        return $this->lineNumber;
    }

    /**
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function isEndOfFile(): bool
    {
        $this->assertOpened();

        return feof($this->handler);
    }

    /**
     * Warning, this function will return exactly the same value as the internal function used by the subsystem.
     *
     * @param null|int $length
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    abstract public function readRaw($length = null);

    /**
     * This methods rewinds the file to the first line of data, skipping the headers.
     *
     * @throws \RuntimeException
     */
    public function rewind(): void
    {
        $this->assertOpened();
        if (!rewind($this->handler)) {
            throw new \RuntimeException("Unable to rewind '{$this->getResourceName()}'");
        }
        $this->lineNumber = 1;
    }

    /**
     * @throws \RuntimeException
     *
     * @return int
     */
    public function tell(): int
    {
        $this->assertOpened();

        return ftell($this->handler);
    }

    /**
     * @param int $offset
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function seek($offset): int
    {
        $this->assertOpened();
        $this->seekCalled = true;

        return fseek($this->handler, $offset);
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        if ($this->closed) {
            return true;
        }

        $this->closed = fclose($this->handler);

        return $this->closed;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Closes the resource when the object is destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @throws \RuntimeException
     */
    protected function assertOpened(): void
    {
        if ($this->closed) {
            throw new \RuntimeException("{$this->getResourceName()} was closed earlier");
        }
    }

    /**
     * @return string
     */
    abstract protected function getResourceName(): string;
}
