<?php

namespace Exo\Loader;

use Exo\Model\Exo;
use Exo\Model\Package;
use Exo\Model\Action;

class ExoLoader
{
    public function load(array $config): Exo
    {
        $exo = new Exo();
        foreach ($config['packages'] as $name=>$config) {
            $package = new Package();
            $package->setName($name);
            $package->setDescription($config['description'] ?? null);
            $filename = $config['__filename__'] ?? null;
            if (!$filename) {
                throw new RuntimeException("Unknown filename for package $name");
            }
            $path = dirname($filename);
            $package->setPath($path);
            $package->setConfigSchema($config['config'] ?? null);
            $exo->getPackages()->add($package);
            foreach ($config['actions'] as $name=>$config) {
                if (isset($config['__filename__'])) {
                    $filename = $config['__filename__'];
                    $path = dirname($filename);
                }
                $action = new Action();
                $action->setName($name);
                $action->setDescription($config['description'] ?? null);
                $action->setInterpreter($config['interpreter'] ?? null);
                $action->setHandler($path . '/' . $config['handler'] ?? null);
                $action->setInputSchema($config['input'] ?? null);
                $action->setOutputSchema($config['output'] ?? null);
                $action->setPackage($package);
                $package->getActions()->add($action);
            }
        }
        return $exo;
    }
}