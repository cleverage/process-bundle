<?php


namespace CleverAge\ProcessBundle\Filesystem;


class JsonStreamFile
{

    /** @var \SplFileObject */
    protected $file;

    /** @var int */
    protected $lineCount;

    /** @var int */
    protected $currentLine = 0;

    /**
     * JsonStreamFile constructor.
     *
     * @param string $filename
     * @param string $mode
     */
    public function __construct(string $filename, $mode = 'rb')
    {
        $this->file = new \SplFileObject($filename, $mode);
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
                $this->file->next();
            }
            $this->rewind();

            $this->lineCount = $line;
        }

        return $this->lineCount;
    }

    /**
     * @return int
     */
    public function getCurrentLine(): int
    {
        return $this->currentLine;
    }

    /**
     * @return bool
     */
    public function isEndOfFile(): bool
    {
        return $this->file->eof();
    }

    /**
     * Return an array containing current data and moving the file pointer
     *
     * @return array|null
     */
    public function readLine(): ?array
    {
        if ($this->isEndOfFile()) {
            return null;
        }

        $rawLine = $this->file->fgets();
        $this->currentLine++;

        return json_decode($rawLine, true);
    }

    /**
     * @param $item
     *
     * @return int
     */
    public function writeLine($item): int
    {
        if (!is_array($item) && !is_scalar($item)) {
            throw new \InvalidArgumentException(
                sprintf('%s only supports items of type scalar or array', __CLASS__)
            );
        }

        $this->file->fwrite(json_encode($item) . PHP_EOL);
        $this->currentLine++;

        return $this->currentLine;
    }

    /**
     * Rewind data to array
     */
    public function rewind(): void
    {
        $this->file->rewind();
        $this->currentLine = 0;
    }
}
