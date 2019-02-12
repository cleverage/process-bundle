<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Addon\Rest\Client;

use CleverAge\ProcessBundle\Addon\Rest\Exception\RestRequestException;
use Httpful\Http;
use Httpful\Request;
use Httpful\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractRestClient
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class Client implements ClientInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $code;

    /** @var string */
    private $uri;

    /**
     * Shopify constructor.
     *
     * @param LoggerInterface $logger
     * @param string          $code
     * @param string          $uri
     */
    public function __construct(LoggerInterface $logger, string $code, string $uri)
    {
        $this->logger = $logger;
        $this->code = $code;
        $this->uri = $uri;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function geUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @param array $options
     *
     * @return Response
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws RestRequestException
     * @throws \Exception
     */
    public function call(array $options = []): Response
    {
        $options = $this->getOptions($options);

        $request = $this->initializeRequest($options);
        $this->setRequestQueryParameters($request, $options);
        $this->setRequestHeader($request, $options);

        try {
            return $request->send();
        } catch (\Exception $e) {
            $this->logger->error(
                'Rest request failed',
                [
                    'url' => $request->uri,
                    'error' => $e->getMessage(),
                ]
            );
            throw new RestRequestException('Rest request failed', 0, $e);
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'url',
            ]
        );

        $resolver->setDefault('method', 'GET');
        $resolver->setDefault('url_parameters', []);
        $resolver->setDefault('query_parameters', []);
        $resolver->setDefault('headers', []);
        $resolver->setDefault('sends', 'json');
        $resolver->setDefault('expects', 'json');

        $resolver->setAllowedTypes('url', ['string']);
        $resolver->setAllowedTypes('method', ['string']);
        $resolver->setAllowedTypes('sends', ['string']);
        $resolver->setAllowedTypes('expects', ['string']);
        $resolver->setAllowedTypes('url_parameters', ['array']);
        $resolver->setAllowedTypes('query_parameters', ['array']);
        $resolver->setAllowedTypes('headers', ['array']);
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function getOptions(array $options = []): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    /**
     * @param array $options
     *
     * @return Request
     *
     * @throws RestRequestException
     */
    protected function initializeRequest(array $options = []): Request
    {
        if (!in_array(
            $options['method'],
            [Http::HEAD, Http::GET, Http::POST, Http::PUT, Http::DELETE, Http::OPTIONS, Http::TRACE, Http::PATCH],
            true
        )) {
            throw new RestRequestException(sprintf('%s is not an HTTP method', $options['method']));
        }
        $request = Request::init($options['method']);
        $request->sends($options['sends']);
        $request->expects($options['expects']);

        return $request;
    }

    /**
     * @param Request $request
     * @param array   $options
     *
     *
     * @throws \Exception
     */
    protected function setRequestQueryParameters(Request $request, array $options = []): void
    {
        $uri = $this->constructUri($options);
        if (Http::GET === $options['method']) {
            if (is_array($options['query_parameters'])) {
                $parametersString = http_build_query($options['query_parameters']);
            } else {
                $parametersString = (string) $options['query_parameters'];
            }
            $uri .= strpos($uri, '?') ? '&' : '?';
            $uri .= $parametersString;
        } elseif ($options['query_parameters']) {
            $request->body($options['query_parameters']);
        }

        $uri = $this->replaceParametersInUri($uri, $options);
        $request->uri($uri);
    }

    /**
     * @param Request $request
     * @param array   $options
     *
     *
     */
    protected function setRequestHeader(Request $request, array $options = []): void
    {
        if ($options['headers']) {
            $request->addHeaders($options['headers']);
        }
    }

    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return sprintf('%s', $this->geUri());
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function constructUri(array $options): string
    {
        $uri = ltrim($options['url'], '/');

        return sprintf('%s/%s', $this->getApiUrl(), $uri);
    }

    /**
     * @param string $uri
     * @param array  $options
     *
     * @return string
     *
     */
    protected function replaceParametersInUri(string $uri, array $options = []): string
    {
        if (array_key_exists('url_parameters', $options)
            && $options['url_parameters']) {

            $search = array_keys($options['url_parameters']);
            array_walk(
                $search,
                function (&$item) {
                    $item = '{'.$item.'}';
                }
            );
            $replace = array_values($options['url_parameters']);
            array_walk(
                $replace,
                function (&$item) {
                    $item = rawurlencode($item);
                }
            );

            $uri = str_replace($search, $replace, $uri);
        }

        return $uri;
    }
}
