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

/**
 * Interface ClientInterface
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
interface ClientInterface
{
    /**
     * Return the code of the client used in client registry.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Return the URI of the WSDL file or NULL if working in non-WSDL mode.
     *
     * @return string
     */
    public function getWsdl(): ?string;

    /**
     * Set the URI of the WSDL file or NULL if working in non-WSDL mode.
     *
     * @param string $wsdl
     *
     * @return void
     */
    public function setWsdl(?string $wsdl): void;

    /**
     * Return the Soap client options
     *
     * @see http://php.net/manual/en/soapclient.soapclient.php
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Set the Soap client options
     *
     * @see http://php.net/manual/en/soapclient.soapclient.php
     *
     * @param array $options
     *
     * @return void
     */
    public function setOptions(array $options): void;

    /**
     * @return string
     */
    public function getLastRequest(): string;

    /**
     * @return string
     */
    public function getLastRequestHeaders(): string;

    /**
     * @return string
     */
    public function getLastResponse(): string;

    /**
     * @return string
     */
    public function getLastResponseHeaders(): string;

    /**
     * Call Soap method
     *
     * @param string $method
     * @param array  $input
     *
     * @return mixed
     */
    public function call(string $method, array $input = []);
}
