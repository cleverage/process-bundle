<?php

namespace App;

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\EventListener\DataQueueEventListener;
use CleverAge\ProcessBundle\Logger\ProcessLogger;
use CleverAge\ProcessBundle\Logger\TaskLogger;
use CleverAge\ProcessBundle\Manager\ProcessManager;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SetPublicServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition(ProcessManager::class)->setPublic(true);
        $container->getDefinition(ProcessConfigurationRegistry::class)->setPublic(true);
        $container->getDefinition(TransformerRegistry::class)->setPublic(true);
        $container->getDefinition(DataQueueEventListener::class)->setPublic(true);
        $container->getDefinition(ProcessLogger::class)->setPublic(true);
        $container->getDefinition(TaskLogger::class)->setPublic(true);
        $container->getDefinition(ContextualOptionResolver::class)->setPublic(true);
    }
}
