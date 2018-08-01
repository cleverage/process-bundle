<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Database;

use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Persistence\ManagerRegistry;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fetch entities from doctrine
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DatabaseUpdaterTask extends AbstractConfigurableTask
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(ProcessState $state)
    {
        $statement = $this->initializeStatement($state);

        // Handle empty results
        if (false === $statement->execute()) {
            $this->logger->critical(
                'Error while executing query: '.$statement->errorInfo(),
                $state->getLogContext()
            );
            $state->setStopped(true);

            return;
        }
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    protected function initializeStatement(ProcessState $state)
    {
        $connection = $this->getConnection($state);

        return $connection->executeQuery($this->getOption($state, 'sql'));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'sql',
            ]
        );
        $resolver->setAllowedTypes('sql', ['string']);
        $resolver->setDefaults(
            [
                'connection' => null,
            ]
        );
        $resolver->setAllowedTypes('connection', ['NULL', 'string']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection(ProcessState $state)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */

        return $this->doctrine->getConnection($this->getOption($state, 'connection'));
    }
}
