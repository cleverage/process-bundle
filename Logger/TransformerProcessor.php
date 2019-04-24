<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Logger;

/**
 * Class TransformerProcessor
 *
 * @package CleverAge\ProcessBundle\Logger
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class TransformerProcessor extends AbstractProcessor
{
    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        $record = parent::__invoke($record);

        $recordExtra = array_key_exists('extra', $record) ? $record['extra'] : [];
        $this->addTaskInfoToRecord($recordExtra);
        $record['extra'] = $recordExtra;

        return $record;
    }
}
