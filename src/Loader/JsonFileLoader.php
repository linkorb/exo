<?php

namespace Exo\Loader;

use RuntimeException;

class JsonFileLoader
{
    public function load($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        if (!$data) {
            throw new RuntimeException("Failed to parse " . $filename);
        }

        $this->resolve(dirname($filename), $data);

        return $data;
    }

    public function resolve($baseDir, &$data)
    {
        // Do nothing if not an array
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $k=>&$v) {
            if (isset($v['$ref'])) {
                $filename = $baseDir . '/' . $v['$ref'];
                $subData = $this->load($filename);
                $data[$k] = $subData;
            }

            $this->resolve($baseDir, $v);
        }
    }
}
