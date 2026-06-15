<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        $container->import('../config/services.yaml');
        if (is_file($path = $this->getProjectDir().'/config/services_'.$this->environment.'.yaml')) {
            $container->import($path);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        if (is_file($path = $this->getProjectDir().'/config/routes/'.$this->environment.'/*.yaml')) {
            $routes->import($path);
        }
        $routes->import('../config/routes.yaml');
    }
}
