<?php

namespace CleverAge\ProcessBundle\Logger;

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class AbstractProcessor
 *
 * @package CleverAge\ProcessBundle\Logger
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class AbstractProcessor
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * AbstractProcessor constructor.
     *
     * @param ProcessManager      $processManager
     * @param NormalizerInterface $normalizer
     */
    public function __construct(
        ProcessManager $processManager,
        NormalizerInterface $normalizer
    ) {
        $this->processManager = $processManager;
        $this->normalizer = $normalizer;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if (array_key_exists('context', $record)
            && $record['context']) {
            $record['context'] = $this->normalizeRecordData($record['context']);
        }

        $this->processManager->getProcessHistory();
        $recordExtra = array_key_exists('extra', $record) ? $record['extra'] : [];
        $this->addProcessInfoToRecord($recordExtra);
        $record['extra'] = $recordExtra;

        return $record;
    }

    /**
     * @param array $record
     * @return array
     */
    protected function normalizeRecordData(array $record): array
    {
        $newRecord = [];
        foreach ($record as $recordName => $recordValue) {
            $this->addToRecord($newRecord, $recordName, $recordValue);
        }

        return $newRecord;
    }

    /**
     * @param array $record
     * @return void
     */
    protected function addProcessInfoToRecord(array &$record): void
    {
        $processHistory = $this->processManager->getProcessHistory();
        if (!$processHistory) {
            return;
        }

        $this->addToRecord($record, 'process_id', $processHistory->getId());
        $this->addToRecord($record, 'process_code', $processHistory->getProcessCode());
        $this->addToRecord($record, 'process_context', $processHistory->getContext());
    }

    /**
     * @param array $record
     * @return void
     */
    protected function addTaskInfoToRecord(array &$record): void
    {
        $taskConfiguration = $this->processManager->getTaskConfiguration();
        if (!$taskConfiguration) {
            return;
        }
        $this->addToRecord($record, 'task_code', $taskConfiguration->getCode());
        $this->addToRecord($record, 'task_service', $taskConfiguration->getServiceReference());

        $state = $taskConfiguration->getState();
        if (!$state) {
            return;
        }
        if ($state->hasError()) {
            $this->addToRecord($record, 'error', $state->getError());
        }

        if ($state->getException()) {
            $this->addToRecord($record, 'exception', $state->getException());
        }
    }

    /**
     * @param array  $record
     * @param string $name
     * @param mixed  $data
     * @return void
     */
    protected function addToRecord(array &$record, $name, $data): void
    {
        if (!array_key_exists($name, $record)) {
            if ($this->normalizer->supportsNormalization($data, 'json')) {
                $record[$name] = $this->normalizer->normalize(
                    $data,
                    'json'
                );
            } else {
                $record[$name] = json_decode(json_encode($data), true);
            }
        }
    }
}
