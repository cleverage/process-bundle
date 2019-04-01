<?php


namespace CleverAge\ProcessBundle\Task\File\JsonStream;


use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

class JsonStreamReaderTask implements IterableTaskInterface
{
    /** @var JsonStreamFile */
    protected $file;

    public function execute(ProcessState $state)
    {
        if ($this->file === null) {
            $this->file = new JsonStreamFile($this->getFilePath($state), 'rb');
        }

        $line = $this->file->readLine();
        if (isset($line)) {
            $state->setOutput($line);
        } else {
            $state->setSkipped(true);
        }

    }

    public function next(ProcessState $state)
    {
        $eof = $this->file->isEndOfFile();
        if ($eof) {
            $this->file = null;
        }

        return !$eof;
    }

    protected function getFilePath(ProcessState $state)
    {
        return $state->getInput();
    }


}
