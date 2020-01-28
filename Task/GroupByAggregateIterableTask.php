<?php declare(strict_types=1);

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\BlockingTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Attempt to aggregate inputs in an associative array with a key formed by configurable fields of the input.
 * This task could be used to remove duplicates from the aggregate.
 *
 * @author Alix Mauro <amauro@clever-age.com>
 */
class GroupByAggregateIterableTask extends AbstractConfigurableTask implements BlockingTaskInterface
{
    /** @var string */
    const GROUP_BY_OPTION = 'group_by_accessors';

    /** @var array */
    protected $result;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(PropertyAccessorInterface $accessor)
    {
        $this->result = [];
        $this->accessor = $accessor;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        $groupByAccessors = $options[self::GROUP_BY_OPTION];

        $keyParts = [];
        foreach ($groupByAccessors as $groupByAccessor) {
            try {
                $keyParts[] = $this->accessor->getValue($input, $groupByAccessor);
            } catch (\Exception $e) {
                $state->addErrorContextValue('property', $groupByAccessor);
                $state->setException($e);

                return;
            }
        }

        $key = implode('-', $keyParts);
        $this->result[$key] = $input;
    }

    /**
     * {@inheritDoc}
     */
    public function proceed(ProcessState $state): void
    {
        if (0 === \count($this->result)) {
            $state->setSkipped(true);
        } else {
            $state->setOutput($this->result);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                self::GROUP_BY_OPTION,
            ]
        );
        $resolver->setAllowedTypes(self::GROUP_BY_OPTION, ['array']);
    }
}
