<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Move the file passed as input, requires the destination path in options
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class FileMoverTask extends AbstractConfigurableTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $fs = new Filesystem();
        $file = $state->getInput();
        if (!$fs->exists($file)) {
            throw new \UnexpectedValueException("File does not exists: '{$file}'");
        }
        $dest = $options['destination'];
        if (is_dir($dest)) {
            $dest = rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename($file);
        }
        if ($options['autoincrement']) {
            $dest = $this->makeFilenameUnique($dest);
        }
        $fs->rename($file, $dest, $options['overwrite']);
        $state->setOutput($dest);
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
                'destination',
            ]
        );
        $resolver->setAllowedTypes('destination', ['string']);
        $resolver->setDefaults(
            [
                'overwrite' => false,
                'autoincrement' => false,
            ]
        );
        $resolver->setAllowedTypes('overwrite', ['bool']);
        $resolver->setAllowedTypes('autoincrement', ['bool']);
    }

    /**
     * @param string $dest
     *
     * @return string
     */
    protected function makeFilenameUnique($dest)
    {
        $fs = new Filesystem();
        $i = 1;
        while ($fs->exists($dest)) {
            if (!preg_match('/^(.*?)(-\d+)?(\.[^\.]*)$/', $dest, $matches)) {
                $dest .= '-'.$i;
            }
            $dest = $matches[1].'-'.$i.$matches[3];
            $i++;
        }

        return $dest;
    }
}
