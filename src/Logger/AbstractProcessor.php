<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Logger;

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Monolog\LogRecord;

class AbstractProcessor
{
    public function __construct(
        protected ProcessManager $processManager
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (! empty($record->context)) {
            $context = $this->normalizeRecordData($record->context);
            $record = new LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $context,
                $record->extra,
                $record->formatted
            );
        }

        $recordExtra = $record->extra;
        $this->addProcessInfoToRecord($recordExtra);
        $record->extra = $recordExtra;

        return $record;
    }

    protected function normalizeRecordData(array $record): array
    {
        $newRecord = [];
        foreach ($record as $recordName => $recordValue) {
            $this->addToRecord($newRecord, $recordName, $recordValue);
        }

        return $newRecord;
    }

    protected function addProcessInfoToRecord(array &$record): void
    {
        $processHistory = $this->processManager->getProcessHistory();
        if (! $processHistory) {
            return;
        }

        $this->addToRecord($record, 'process_id', $processHistory->getId());
        $this->addToRecord($record, 'process_code', $processHistory->getProcessCode());
        $this->addToRecord($record, 'process_context', $processHistory->getContext());
    }

    protected function addTaskInfoToRecord(array &$record): void
    {
        $taskConfiguration = $this->processManager->getTaskConfiguration();
        if (! $taskConfiguration) {
            return;
        }
        $this->addToRecord($record, 'task_code', $taskConfiguration->getCode());
        $this->addToRecord($record, 'task_service', $taskConfiguration->getServiceReference());

        $state = $taskConfiguration->getState();
        if (! $state) {
            return;
        }
        if ($state->hasErrorOutput()) {
            $this->addToRecord($record, 'error', $state->getErrorOutput());
        }

        if ($state->getException()) {
            $this->addToRecord($record, 'exception', $state->getException());
        }
    }

    /**
     * @param string $name
     */
    protected function addToRecord(array &$record, $name, mixed $data): void
    {
        $record[$name] = $data;
    }
}
