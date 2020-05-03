<?php

namespace Exo\Core\Loader;

use Exo\Core\Exception;
use Exo\Core\Model\Action;

class ActionLoader
{
    public function load(string $path, array $config): Action
    {
        $actionName = $config['name'];
        if (!isset($config['handler'])) {
            throw new Exception\ConfigurationException("Undefined handler for action '$actionName'");
        }
        
        $handler = $path . '/' . $config['handler'];
        if (!file_exists($handler)) {
            throw new Exception\ConfigurationException("Handler not found for action '$actionName' " . $handler);
        }

        $interpreter = $config['interpreter'] ?? null;
        if (!$interpreter) {
            $ext = pathinfo($handler, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'php':
                    $interpreter = 'php';
                    break;
                case 'js':
                    $interpreter = 'node';
                    break;
                case 'sh':
                    $interpreter = 'sh';
                    break;
                default:
                    throw new Exception\ConfigurationException("Can't determine interpreter for action '$fqan'. Please specify.");
            }
        }
        
        $action = new Action();
        $action->setName($config['name'] ?? null);
        $action->setDescription($config['description'] ?? null);
        $action->setInterpreter($interpreter);
        $action->setHandler($handler);
        $action->setInputSchema($config['input'] ?? null);
        $action->setOutputSchema($config['output'] ?? null);
        return $action;
    }
}