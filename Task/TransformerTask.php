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

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform an array of data based on mapping and sub-transformers
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerTask extends AbstractConfigurableTask
{
    /** @var ConfigurableTransformerInterface */
    protected $transformer;

    /**
     * @param TransformerRegistry $transformerRegistry
     * @param string              $transformerCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     */
    public function __construct(TransformerRegistry $transformerRegistry, $transformerCode)
    {
        $this->transformer = $transformerRegistry->getTransformer($transformerCode);
        if (!$this->transformer instanceof ConfigurableTransformerInterface) {
            throw new \UnexpectedValueException(
                "Transformer {$transformerCode} must be a ConfigurableTransformerInterface"
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $this->transformer->configureOptions($resolver);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $output = null;
        $options = $this->getOptions($state);
        $transformerOptions = $options;
        unset(
            $transformerOptions[AbstractConfigurableTask::STOP_ON_ERROR],
            $transformerOptions[AbstractConfigurableTask::LOG_ERRORS],
            $transformerOptions[AbstractConfigurableTask::SKIP_ON_ERROR]
        );

        try {
            $output = $this->transformer->transform(
                $state->getInput(),
                $transformerOptions
            );
        } catch (\Exception $e) {
            $state->setError($state->getInput());
            if ($options[AbstractConfigurableTask::STOP_ON_ERROR]) {
                $state->stop($e);

                return;
            }
            if ($options[AbstractConfigurableTask::LOG_ERRORS]) {
                $state->log('PropertySetter exception: '.$e->getMessage(), LogLevel::ERROR);
            }
            if ($options[AbstractConfigurableTask::SKIP_ON_ERROR]) {
                $state->setSkipped(true);

                return;
            }
        }
        $state->setOutput($output);
    }
}
