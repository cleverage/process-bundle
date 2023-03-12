<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Runtime error that should wrap any Transformation error
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerException extends \RuntimeException implements ProcessExceptionInterface
{
    /** @var string */
    protected $transformerCode;

    /** @var string */
    protected $targetProperty;

    /**
     * {@inheritDoc}
     */
    public function __construct($transformerCode, $code = 0, \Throwable $previous = null)
    {
        $this->transformerCode = $transformerCode;

        parent::__construct('', $code, $previous);
        $this->updateMessage();
    }

    /**
     * @param string $targetProperty
     */
    public function setTargetProperty(string $targetProperty): void
    {
        $this->targetProperty = $targetProperty;
        $this->updateMessage();
    }

    protected function updateMessage()
    {
        if (isset($this->targetProperty)) {
            $m = sprintf(
                "For target property '%s', transformation '%s' have failed",
                $this->targetProperty,
                $this->transformerCode
            );
        } else {
            $m = sprintf(
                "Transformation '%s' have failed",
                $this->transformerCode
            );
        }
        if ($this->getPrevious()) {
            $m .= ": {$this->getPrevious()->getMessage()}";
        }
        $this->message = $m;
    }
}
