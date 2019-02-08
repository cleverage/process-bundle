<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Cache;

use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Class DeleterTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DeleterTask extends AbstractCacheTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $keyValue = $this->getKeyCache($state);
        $input = $state->getInput();

        $this->getCache()->deleteItem($keyValue);

        $state->setOutput($input);
    }
}
