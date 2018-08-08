<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Doctrine;

use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

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
        $entity = $state->getInput();
        if (null === $entity) {
            $entityManager = $this->getManager($state);
        } else {
            $class = ClassUtils::getClass($entity);
            $entityManager = $this->doctrine->getManagerForClass($class);
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \UnexpectedValueException("No manager found for class {$class}");
            }
        }

        $entityManager->clear();
    }
}
