<?php

namespace JMose\CommandSchedulerBundle;

use JMose\CommandSchedulerBundle\DependencyInjection\JMoseCommandSchedulerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class JMoseCommandSchedulerBundle.
 */
class JMoseCommandSchedulerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     *
     * @return JMoseCommandSchedulerExtension
     */
    public function getContainerExtension(): JMoseCommandSchedulerExtension
    {
        $class = $this->getContainerExtensionClass();

        return new $class();
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensionClass(): string
    {
        return JMoseCommandSchedulerExtension::class;
    }
}
