<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Exo\Loader\ConfigLoader;
use Exo\Loader\ExoLoader;
use Exo\Model\Exo;

use RuntimeException;

abstract class AbstractCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function getConfig(): array
    {
        $configLoader = new ConfigLoader();
        $config = $configLoader->loadFromEnv('EXO_CONFIG');
        return $config;
    }

    public function getExo(): Exo
    {
        $config = $this->getConfig();
        $exoLoader = new ExoLoader();
        $exo = $exoLoader->load($config);
        return $exo;
    }
}
