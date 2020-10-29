<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Command\Command;
use LinkORB\Config\ConfigLoader;
use Exo\Core\Loader\ActionLoader;
use Exo\Core\Exception;
use Exo\Core\Model\Exo;
use RuntimeException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    public function getExo(InputInterface $input, OutputInterface $output): Exo
    {
        $configLoader = new ConfigLoader();
        $actionLoader = new ActionLoader();

        // Setup logging
        $filename = getenv("EXO_LOG");
        $logger = new Logger($this->getName());
        if ($filename) {
            $logger->pushHandler(new StreamHandler($filename, Logger::DEBUG));
        }

        if ($output->isVerbose()) {
            $logger->pushHandler(new StreamHandler('/dev/stdout', Logger::DEBUG));
        }

        // Instantiate Exo instance
        $exo = new Exo($logger);

        // Load actions
        $paths = explode(',', getenv("EXO_ACTIONS"));
        foreach ($paths as $part) {
            $paths = glob($part);
            foreach ($paths as $path) {
                $filename = $path . '/exo.action.yaml';
                if (file_exists($filename)) {
                    $actionConfig = $configLoader->loadFile($filename);
                    $action = $actionLoader->load(dirname($filename), $actionConfig);

                    $exo->getActions()->add($action);
                }
            }
        }

        return $exo;
    }
}
