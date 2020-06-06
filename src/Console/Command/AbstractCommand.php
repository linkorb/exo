<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Command\Command;
use LinkORB\Config\ConfigLoader;
use Exo\Core\Loader\ActionLoader;
use Exo\Core\Exception;
use Exo\Core\Model\Exo;
use RuntimeException;

abstract class AbstractCommand extends Command
{
    public function getExo(): Exo
    {
        $configLoader = new ConfigLoader();
        $actionLoader = new ActionLoader();
        $exo = new Exo();
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
