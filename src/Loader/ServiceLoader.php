<?php

namespace Exo\Loader;

use Exo\Model\Service;
use Exo\Model\Action;
use Symfony\Component\Console\Exception\RuntimeException;

class ServiceLoader
{
    public function load($filename)
    {
        $jsonFileLoader = new JsonFileLoader();
        $data = $jsonFileLoader->load($filename);

        $service = new Service();
        $service->setName($data['name'] ?? null);
        $service->setDescription($data['description'] ?? null);
        $actionLoader = new ActionLoader();
        foreach ($data['actions'] as $name=>$data) {
            $action = $actionLoader->load(dirname($filename) . '/' . $data);
            $service->getActions()->add($action);
        }
        return $service;
    }
}
