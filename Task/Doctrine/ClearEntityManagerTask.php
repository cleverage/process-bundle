<?php declare(strict_types=1);
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2019 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Doctrine;

use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\ORM\ORMInvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

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
     * @throws ORMInvalidArgumentException
     * @throws ExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function execute(ProcessState $state)
    {
        $entityManager = $this->getManager($state);
        $entityManager->clear();
    }
}
