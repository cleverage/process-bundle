<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Addon\Doctrine\Task\EntityManager;

use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Clear Doctrine's unit of work
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ClearEntityManagerTask extends AbstractDoctrineTask
{
    /**
     * @param ProcessState $state
     *
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $entityManager = $this->getManager($state);
        $entityManager->clear();
    }
}
