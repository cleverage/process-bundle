<?php

declare(strict_types=1);

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the whole input file and outputs its content.
 */
class InputFileReaderTask extends FileReaderTask
{
    protected function getOptions(ProcessState $state): array
    {
        $options = parent::getOptions($state);
        if (null !== $state->getInput()) {
            $options['filename'] = $state->getInput();
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->remove('filename');
    }
}
