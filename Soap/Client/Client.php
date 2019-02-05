<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Soap\Client;

use Psr\Log\LoggerInterface;

/**
 * Class Client
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class Client implements ClientInterface
{
    /** @var string */
    private $code;

    /** @var string */
    private $wsdl;

    /** @var array */
    private $options = [];

    /** @var LoggerInterface */
    private $logger;

    /** @var \SoapClient */
    private $soapClient;

    /** @var string */
    private $lastRequest;

    /** @var string */
    private $lastRequestHeaders;

    /** @var string */
    private $lastResponse;

    /** @var string */
    private $lastResponseHeaders;

    /**
     * Client constructor.
     *
     * @param LoggerInterface $logger
     * @param string          $code
     * @param string          $wsdl
     * @param array           $options
     */
    public function __construct(LoggerInterface $logger, string $code, string $wsdl, array $options)
    {
        $this->logger = $logger;
        $this->code = $code;
        $this->wsdl = $wsdl;
        $this->options = $options;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     * @throws \UnexpectedValueException
     */
    public function getCode(): string
    {
        if (!$this->code) {
            throw new \UnexpectedValueException('Client code is not defined');
        }

        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getWsdl(): ?string
    {
        return $this->wsdl;
    }

    /**
     * {@inheritdoc}
     */
    public function setWsdl(?string $wsdl): void
    {
        $this->wsdl = $wsdl;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return \SoapClient|null
     */
    public function getSoapClient(): ?\SoapClient
    {
        return $this->soapClient;
    }

    /**
     * @param \SoapClient $soapClient
     */
    public function setSoapClient(\SoapClient $soapClient): void
    {
        $this->soapClient = $soapClient;
    }

    /**
     * @return string
     */
    public function getLastRequest(): string
    {
        return $this->lastRequest;
    }

    /**
     * @param string $lastRequest
     */
    public function setLastRequest(string $lastRequest): void
    {
        $this->lastRequest = $lastRequest;
    }

    /**
     * @return string
     */
    public function getLastRequestHeaders(): string
    {
        return $this->lastRequestHeaders;
    }

    /**
     * @param string $lastRequestHeaders
     */
    public function setLastRequestHeaders(string $lastRequestHeaders): void
    {
        $this->lastRequestHeaders = $lastRequestHeaders;
    }

    /**
     * @return string
     */
    public function getLastResponse(): string
    {
        return $this->lastResponse;
    }

    /**
     * @param string $lastResponse
     */
    public function setLastResponse(string $lastResponse): void
    {
        $this->lastResponse = $lastResponse;
    }

    /**
     * @return string
     */
    public function getLastResponseHeaders(): string
    {
        return $this->lastResponseHeaders;
    }

    /**
     * @param string $lastResponseHeaders
     */
    public function setLastResponseHeaders(string $lastResponseHeaders): void
    {
        $this->lastResponseHeaders = $lastResponseHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function call(string $method, array $input = [])
    {
        $this->initializeSoapClient();

        $callMethod = sprintf('soapCall%s', ucfirst($method));
        if (method_exists($this, $callMethod)) {
            return $this->$callMethod($input);
        }

        $this->getLogger()->notice(
            sprintf("Soap call '%s' on '%s'", $method, $this->getWsdl())
        );

        return $this->doSoapCall($method, $input);
    }

    /**
     * @param string $method
     * @param array  $input
     *
     * @return bool|mixed
     */
    protected function doSoapCall(string $method, array $input = [])
    {
        if (!$this->getSoapClient()) {
            throw new \InvalidArgumentException('Soap client is not initialized');
        }
        try {
            $result = $this->getSoapClient()->__soapCall($method, [$input]);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (\SoapFault $e) {
            $this->getLastRequestTrace();
            $this->getLogger()->alert(
                sprintf("Soap call '%s' on '%s' failed : %s", $method, $this->getWsdl(), $e->getMessage()),
                $this->getLastRequestTraceArray()
            );

            return false;
        }

        $this->getLastRequestTrace();

        if (array_key_exists('trace', $this->getOptions()) && $this->getOptions()['trace']) {
            $this->getLogger()->debug(
                sprintf("Trace of soap call '%s' on '%s'", $method, $this->getWsdl()),
                $this->getLastRequestTraceArray()
            );
        }

        return $result;
    }

    /**
     * Initialize \SoapClient object
     *
     * @return void
     */
    protected function initializeSoapClient(): void
    {
        if (!$this->getSoapClient()) {
            $options = array_merge($this->getOptions(), ['trace' => true]);
            $this->setSoapClient(new \SoapClient($this->getWsdl(), $options));
        }
    }

    protected function getLastRequestTrace(): void
    {
        if ($this->getSoapClient()) {
            $this->setLastRequest($this->getSoapClient()->__getLastRequest());
            $this->setLastRequestHeaders($this->getSoapClient()->__getLastRequestHeaders());
            $this->setLastResponse($this->getSoapClient()->__getLastResponse());
            $this->setLastResponseHeaders($this->getSoapClient()->__getLastResponseHeaders());
        }
    }

    /**
     * @return array
     */
    protected function getLastRequestTraceArray(): array
    {
        return [
            'LastRequest' => $this->getLastRequest(),
            'LastRequestHeaders' => $this->getLastRequestHeaders(),
            'LastResponse' => $this->getLastResponse(),
            'LastResponseHeaders' => $this->getLastResponseHeaders(),
        ];
    }
}
