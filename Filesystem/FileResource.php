<?php declare(strict_types=1);

namespace CleverAge\ProcessBundle\Filesystem;

/**
 * Read and write files through a simple API.
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class FileResource extends AbstractCommonResource implements FileInterface, WritableFileInterface, SeekableFileInterface
{
    /**
     * Warning, this function will return exactly the same value as the fgets() function.
     *
     * @param null|int $length
     *
     * @throws \RuntimeException
     *
     * @return string|false
     */
    public function readRaw($length = null)
    {
        $this->assertOpened();
        ++$this->lineNumber;

        return fgets($this->handler, $length);
    }

    /**
     * @param int|null $length
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     *
     * @return string|null
     */
    public function readLine($length = null): ?string
    {
        if ($this->seekCalled) {
            $filePosition = "at position {$this->tell()}";
        } else {
            $filePosition = "on line {$this->getLineNumber()}";
        }
        $line = $this->readRaw($length);

        if (false === $line) {
            if ($this->isEndOfFile()) {
                return null;
            }
            $message = "Unable to read line {$filePosition} for {$this->getResourceName()}";
            throw new \UnexpectedValueException($message);
        }

        return $line;
    }

    /**
     * Warning, this function will return exactly the same value as the fwrite() function.
     *
     * @param string $line
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function writeRaw(string $line): int
    {
        $this->assertOpened();
        ++$this->lineNumber;

        return fwrite($this->handler, $line);
    }

    /**
     * @param string $line
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    public function writeLine(string $line): int
    {
        $length = $this->writeRaw($line);
        if (false === $length) {
            throw new \RuntimeException("Unable to write data to {$this->getResourceName()}");
        }

        return $length;
    }

    /**
     * @return string
     */
    protected function getResourceName(): string
    {
        return "File resource '{$this->handler}'";
    }
}
