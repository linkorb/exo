<?php

namespace Exo\Loader;

use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class ConfigLoader
{
    public function loadFromEnv(string $name)
    {
        $filename = getenv($name);
        if (!$filename) {
            throw new RuntimeException("Environment variable $name is undefined");
        }
        return $this->loadFile(dirname($filename), '/' . basename($filename));
    }

    public function loadFile(string $baseDir, string $filename)
    {
        $fqfn = $baseDir . $filename;
        $content = $this->getFileContent($fqfn);
        // echo "Loaded $baseDir{$filename}" . PHP_EOL;
        $info = pathinfo($filename);
        $extension = $info['extension'] ?? null;
        switch ($extension) {
            case "yaml":
            case "yml":
                $data = Yaml::parse($content);
                break;
            case "json":
                $data = json_decode($content, true);
                break;
            default:
                throw new RuntimeException("Unsupported config file format: " . $extension);
        }
        
        if (!$data) {
            throw new RuntimeException("Failed to parse " . $fqfn);
        }
        $data['__filename__'] = realpath($fqfn);
        
        $this->resolve($baseDir, $data);
        return $data;
    }

    public function getFileContent(string $filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: " . $filename);
        }
        $content = file_get_contents($filename);
        return $content;
    }

    public function resolve(string $baseDir, &$data)
    {
        // Do nothing if not an array
        if (!is_array($data)) {
            // throw new RuntimeException("Data is not an array?");
            return;
        }

        foreach ($data as $k=>&$v) {
            if (isset($v['$import'])) {
                $filename = $v['$import'];
                $filename = $baseDir . '/' . $filename;
                $subData = $this->loadFile(dirname($filename), '/' . basename($filename));
                $this->resolve(dirname($filename), $subData);
                $subData = array_replace_recursive( $subData, $v ?? []);
                $v = $subData;
                unset($v['$import']);
                // print_r($subData);
            }
            $this->resolve($baseDir, $data[$k]);
        }
    }
}