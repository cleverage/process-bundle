<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Addon\Rest\Task;

use CleverAge\ProcessBundle\Addon\Rest\Registry;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RequestTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class RequestTask extends AbstractConfigurableTask
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var Registry */
    protected $registry;

    /**
     * RequestTask constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, Registry $registry)
    {
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     * @param ProcessState $state
     *
     * @throws \CleverAge\ProcessBundle\Addon\Rest\Exception\MissingClientException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        $client = $this->registry->getClient($options['client']);

        $requestOptions = [
            'url' => $options['url'],
            'headers' => $options['headers'],
            'url_parameters' => $options['url_parameters'],
            'query_parameters' => $options['query_parameters'],
            'sends' => $options['sends'],
            'expects' => $options['expects'],
        ];

        $input = $state->getInput() ?: [];
        $requestOptions = array_merge($requestOptions, $input);
        $result = $client->call($requestOptions);

        // Handle empty results
        if (!\in_array($result->code, $options['valid_response_code'], false)) {
            $this->logger->error(
                'REST request failed',
                [
                    'client' => $options['client'],
                    'options' => $options,
                    'raw_headers' => $result->raw_headers,
                    'raw_body' => $result->raw_body,
                ]
            );
            $state->setErrorOutput($result->body);

            if ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($state->getTaskConfiguration()->getErrorStrategy() === TaskConfiguration::STRATEGY_STOP) {
                $state->setStopped(true);
            }

            return;
        }

        $state->setOutput($result->body);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'client',
                'url',
                'method',
            ]
        );
        $resolver->setDefault('headers', []);
        $resolver->setDefault('url_parameters', []);
        $resolver->setDefault('query_parameters', []);
        $resolver->setDefault('sends', 'json');
        $resolver->setDefault('expects', 'json');
        $resolver->setDefault('valid_response_code', [200]);
        $resolver->setAllowedTypes('client', ['string']);
        $resolver->setAllowedTypes('url', ['string']);
        $resolver->setAllowedTypes('method', ['string']);
        $resolver->setAllowedTypes('valid_response_code', ['array']);
    }
}
