<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Runtime error that should wrap any Transformation error.
 */
class TransformerException extends \RuntimeException implements ProcessExceptionInterface
{
    protected string $targetProperty;

    public function __construct(
        protected string $transformerCode,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct('', $code, $previous);
        $this->updateMessage();
    }

    public function setTargetProperty(string $targetProperty): void
    {
        $this->targetProperty = $targetProperty;
        $this->updateMessage();
    }

    protected function updateMessage(): void
    {
        if (isset($this->targetProperty)) {
            $m = sprintf(
                "For target property '%s', transformation '%s' have failed",
                $this->targetProperty,
                $this->transformerCode
            );
        } else {
            $m = sprintf("Transformation '%s' have failed", $this->transformerCode);
        }
        if ($this->getPrevious()) {
            $m .= ": {$this->getPrevious()
                ->getMessage()}";
        }
        $this->message = $m;
    }
}
