<?php

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FileWriterTask
 *
 * @package CleverAge\ProcessBundle\Task
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class FileWriterTask extends AbstractConfigurableTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        $fs = new Filesystem();
        $fs->dumpFile($options['filename'], $state->getInput());

        $state->setOutput($options['filename']);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'filename',
            ]
        );
        $resolver->setAllowedTypes('filename', ['string']);
    }
}