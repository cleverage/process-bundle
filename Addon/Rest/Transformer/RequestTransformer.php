<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Addon\Rest\Transformer;

use CleverAge\ProcessBundle\Addon\Rest\Registry;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RequestTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class RequestTransformer implements ConfigurableTransformerInterface
{

    /** @var LoggerInterface */
    protected $logger;

    /** @var Registry */
    protected $registry;

    /**
     * RequestTransformer constructor.
     *
     * @param Registry $registry
     */
    public function __construct(LoggerInterface $logger, Registry $registry)
    {
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \RuntimeException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \CleverAge\ProcessBundle\Addon\Rest\Exception\MissingClientException
     */
    public function transform($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $client = $this->registry->getClient($options['client']);

        $requestOptions = [
            'url' => $options['url'],
            'headers' => $options['headers'],
            'url_parameters' => $options['url_parameters'],
            'query_parameters' => $options['query_parameters'],
            'sends' => $options['sends'],
            'expects' => $options['expects'],
        ];

        $input = $value ?: [];
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

            throw new TransformerException('REST request failed');
        }

        return $result->body;
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'rest_request';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
