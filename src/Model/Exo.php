<?php

namespace Exo\Model;

use Exo\Utils;
use Exo\Exception;
use Exo\Model\Action;
use Collection\TypedArray;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Exo extends AbstractModel
{
    protected $name;
    protected $description;
    protected $packages = [];

    public function __construct()
    {
        $this->packages = new TypedArray(Package::class);
        $this->requestSchema = json_decode(
            file_get_contents(__DIR__ . '/../../schema/request.schema.json'),
            true
        );
        $this->responseSchema = json_decode(
            file_get_contents(__DIR__ . '/../../schema/response.schema.json'),
            true
        );
    }

    public function getAction(string $fqan): Action
    {
        $part = explode('/', $fqan);
        if (count($part)!=2) {
            throw new Exception\InvalidFqanException("Invalid FQAN: " . $fqan);
        }
        $packageName = $part[0];
        $actionName = $part[1];
        if (!$this->getPackages()->hasKey($packageName)) {
            throw new Exception\UnknownActionException("Unknown action: " . $fqan);
        }
        $package = $this->getPackages()->get($packageName);

        if (!$package->getActions()->hasKey($actionName)) {
            throw new Exception\UnknownActionException("Unknown action: " . $fqan);
        }
        $action = $package->getActions()->get($actionName);
        return $action;
    }

    public function getPackageConfig(string $packageName)
    {
        $prefix = 'EXO__' . strtoupper($packageName) . '__';

        $config = [];
        foreach ($_ENV as $k=>$v) {
            if (strpos($k, $prefix)===0) {
                $k=substr($k, strlen($prefix));
                $config[strtolower($k)] = $v;
            }
        }
        return $config;
    }

    public function handle(array $request): array
    {
        Utils::validateArray($request, $this->requestSchema);

        $fqan = $request['action'] ?? null;
        $action = $this->getAction($fqan);
        $package = $action->getPackage();

        $package->validateConfig($request['config']);
        $action->validateInput($request['input']);

        $handlerFilename = $action->getHandler();
        $interpreter = $action->getInterpreter();
        $cwd = dirname($handlerFilename);

        $envPrefix = 'EXO__' . strtoupper($package->getName()) . '__';
        $env = [];
        foreach ($request['config'] as $k=>$v) {
            $k = $envPrefix . strtoupper($k);
            $env[$k] = $v;
        }
        $stdin = json_encode($request, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $process = new Process(array($interpreter, $handlerFilename), $cwd, $env, $stdin);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        $response = json_decode($output, true);
        if (!$response) {
            throw new Exception\InvalidResponseException($output);
        }
        Utils::validateArray($response, $this->responseSchema);

        $output = $response['output'] ?? [];
        $action->validateOutput($output);
        return $response;
    }
}
