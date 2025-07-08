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

namespace CleverAge\ProcessBundle\Task\File\JsonStream;

use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonStreamWriterTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    protected ?JsonStreamFile $file = null;

    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        if (!$this->file instanceof JsonStreamFile) {
            $this->file = new JsonStreamFile($options['file_path'], 'wb');
        }

        $input = $state->getInput();
        if (!\is_array($input)) {
            throw new \UnexpectedValueException('Input value is not an array');
        }
        $this->file->writeLine($input);
    }

    public function proceed(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $state->setOutput($options['file_path']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['file_path']);
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setNormalizer(
            'file_path',
            static fn (Options $options, $value): string => strtr(
                $value,
                [
                    '{date}' => date('Ymd'),
                    '{date_time}' => date('Ymd_His'),
                    '{timestamp}' => time(),
                    '{unique_token}' => uniqid('', true),
                ]
            )
        );
    }
}
