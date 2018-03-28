<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Easily extendable task to query entities in their repository
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
abstract class AbstractDoctrineQueryTask extends AbstractDoctrineTask
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(
            [
                'class_name',
            ]
        );
        $resolver->setAllowedTypes('class_name', ['string']);
        $resolver->setDefaults(
            [
                'criteria' => [],
                'order_by' => [],
                'limit' => null,
                'offset' => null,
            ]
        );
        $resolver->setAllowedTypes('criteria', ['array']);
        $resolver->setAllowedTypes('order_by', ['array']);
        $resolver->setAllowedTypes('limit', ['NULL', 'integer']);
        $resolver->setAllowedTypes('offset', ['NULL', 'integer']);
    }

    /**
     * @param EntityRepository $repository
     * @param array            $criteria
     * @param array            $orderBy
     * @param int              $limit
     * @param int              $offset
     *
     * @throws \UnexpectedValueException
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder(
        EntityRepository $repository,
        array $criteria,
        array $orderBy,
        $limit = null,
        $offset = null
    ) {
        $qb = $repository->createQueryBuilder('e');
        foreach ($criteria as $field => $value) {
            if (preg_match('/[^a-zA-Z0-9]/', $field)) {
                throw new \UnexpectedValueException("Forbidden field name '{$field}'");
            }
            $parameterName = uniqid('param', false);
            if (null === $value) {
                $qb->andWhere("e.{$field} IS NULL");
            } else {
                if (is_array($value)) {
                    $qb->andWhere("e.{$field} IN (:{$parameterName})");
                } else {
                    $qb->andWhere("e.{$field} = :{$parameterName}");
                }
                $qb->setParameter($parameterName, $value);
            }
        }
        /** @noinspection ForeachSourceInspection */
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy("e.{$field}", $order);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }
        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        return $qb;
    }
}
