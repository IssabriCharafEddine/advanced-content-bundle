<?php

namespace Sherlockode\AdvancedContentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class AdvancedContentExtension
 */
class AdvancedContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $formBuilderManager = $container->getDefinition('Sherlockode\AdvancedContentBundle\Manager\FormBuilderManager');
        $formBuilderManager->addMethodCall('setConfig', [$config['entity_class']]);
    }
}
