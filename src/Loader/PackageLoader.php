<?php

namespace Exo\Loader;

use Exo\Model\Package;
use Exo\Model\Action;
use Symfony\Component\Console\Exception\RuntimeException;

class PackageLoader
{
    public function load($filename)
    {
        $jsonFileLoader = new JsonFileLoader();
        $data = $jsonFileLoader->load($filename);

        $package = new Package();
        $package->setFilename($filename);
        $package->setName($data['name'] ?? null);
        $package->setDescription($data['description'] ?? null);
        $actionLoader = new ActionLoader();
        foreach ($data['actions'] as $name=>$data) {
            $action = $actionLoader->load(dirname($filename) . '/' . $data);
            $package->getActions()->add($action);
        }
        return $package;
    }
}
