<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FileWriterTask
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class FileWriterTask extends AbstractConfigurableTask
{
    /**
     * @param ProcessState $state
     *
     * @throws ExceptionInterface
     * @throws IOException
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
     * @throws AccessException
     * @throws UndefinedOptionsException
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
