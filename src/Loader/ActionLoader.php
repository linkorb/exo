<?php

namespace Exo\Loader;

use Exo\Model\Action;
use Symfony\Component\Console\Exception\RuntimeException;

class ActionLoader
{

    public function load(string $filename)
    {
        $jsonFileLoader = new JsonFileLoader();
        $data = $jsonFileLoader->load($filename);

        $action = new Action();
        $action->setName($data['name']);
        $action->setDescription($data['description'] ?? null);
        $action->setInterpreter($data['interpreter'] ?? null);
        $action->setFilename($filename);
        $handlerFilename = dirname($filename) . '/' . $data['handler'];
        $action->setHandlerFilename($handlerFilename);

        if (isset($data['execution'])) {
            $action->setExecutionType($data['execution']['type']);
            $action->setExecutionArguments($data['execution']);
        }

        $action->setConfigSchema($data['config']['schema'] ?? null);
        $action->setInputSchema($data['input']['schema'] ?? null);
        $action->setOutputSchema($data['output']['schema'] ?? null);

        return $action;
    }
}
