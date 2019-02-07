<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Addon\Rest\Client;

use Httpful\Response;

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
     * Return the URI
     *
     * @return string
     */
    public function geUri(): string;

    /**
     * Set the URI
     *
     * @param string $uri
     *
     * @return void
     */
    public function setUri(string $uri): void;

    /**
     * @param array $options
     *
     * @return \Httpful\Response
     */
    public function call(array $options = []): Response;
}
