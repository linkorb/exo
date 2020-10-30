<?php

namespace Exo\Core\Model;

use Exo\Core\Utils\JsonUtils;
use Exo\Core\Utils\ArrayUtils;
use Exo\Core\Exception;
use Exo\Core\Model\Action;
use Collection\TypedArray;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Psr\Log\LoggerInterface;

/**
 * @method Action[]|TypedArray getActions()
 */
class Exo extends AbstractModel
{
    protected $name;
    protected $description;
    // protected $packages = [];
    protected $actions = [];
    protected $variables = [];
    protected $requestSchema;
    protected $responseSchema;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->actions = new TypedArray(Action::class);
        // $this->packages = new TypedArray(Package::class);

        $this->requestSchema = json_decode(
            file_get_contents(__DIR__ . '/../../../schema/request.schema.json'),
            true
        );
        $this->responseSchema = json_decode(
            file_get_contents(__DIR__ . '/../../../schema/response.schema.json'),
            true
        );

        $this->variables = ArrayUtils::getByPrefix(getenv(), 'EXO__VARIABLE__');
    }

    public function getId(): string
    {
        return getenv('EXO_ID') ?? 'Undefined';
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function hasAction(string $fqan): bool
    {
        return $this->getActions()->hasKey($fqan);
    }

    public function getAction(string $fqan): Action
    {

        if (!$this->hasAction($fqan)) {
            throw new Exception\UnknownActionException("Unknown action: " . $fqan);
        }
        $action = $this->getActions()->get($fqan);
        return $action;
    }

    // public function getPackageConfig(string $packageName)
    // {
    //     $prefix = 'EXO__' . strtoupper($packageName) . '__';

    //     $config = [];
    //     foreach ($_ENV as $k=>$v) {
    //         if (strpos($k, $prefix)===0) {
    //             $k=substr($k, strlen($prefix));
    //             $config[strtolower($k)] = $v;
    //         }
    //     }
    //     return $config;
    // }


    public function safeHandle(array $request): array
    {
        try {
            $response = $this->handle($request);
        } catch (\Exception $e) {
            $response = [
                'status' => 'ERROR',
                'error' => [
                    get_class($e) . ': ' . $e->getMessage(),
                ]
            ];
        }
        return $response;
    }

    public function handle(array $request): array
    {
        $processingStart = microtime(true);
        $this->getLogger()->notice("Request", ['request' => $request]);

        JsonUtils::validateArray($request, $this->requestSchema);
        $fqan = $request['action'] ?? null;
        $requestId = $request['id'] ?? null;

        $action = $this->getAction($fqan);
        // $package = $action->getPackage();

        // $package->validateConfig($request['config']);

        $input = $request['input'] ?? [];


        // Apply variables
        foreach ($input as $k => $v) {
            if (is_string($v)) {
                foreach ($this->variables as $k2 => $v2) {
                    $varName = '{{' . trim($k2) . '}}';
                    if (strpos($v, $varName) !== false) {
                        $v3 = str_replace($varName, $v2, $v);
                        // echo "$k=$v - $k2=$v2 ($v3)\n";
                        $input[$k] = $v3;
                    }
                }
            }
        }

        // Un-flatten multi-dimensional array keys
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($input as $k=>$v) {
            if (is_object($v)) {
                $input[$k] = $v;
            } else {
                unset($input[$k]);
                $k = str_replace('.', '][', $k);
                $k = str_replace('__', '][', $k);
                $propertyAccessor->setValue($input, '[' . $k . ']', $v);
            }
        }

        // print_r($input); exit();
        $action->validateInput($input);
        $request['input'] = $input;
        // print_r($request);exit('DONE'. PHP_EOL);

        $handlerFilename = $action->getHandler();
        $interpreter = $action->getInterpreter();

        $cwd = dirname($handlerFilename);

        $env = [];
        // $envPrefix = 'EXO__' . strtoupper($package->getName()) . '__';
        // foreach ($request['config'] as $k=>$v) {
        //     $k = $envPrefix . strtoupper($k);
        //     $env[$k] = $v;
        // }
        $stdin = json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

        if ($requestId) {
            $response['request'] = [
                'id' => $requestId,
            ];
        }

        $processingEnd = microtime(true);

        $headers = [
            'exoId' => $this->getId(),
            'hostname' => gethostname(),
            'processingStart' => $processingStart,
            'processingDuration' => ($processingEnd - $processingStart),
        ];
        $response = array_merge(['headers' => $headers], $response);
        JsonUtils::validateArray($response, $this->responseSchema);

        $output = $response['output'] ?? [];
        $action->validateOutput($output);

        // Apply output variable mapping
        foreach (($request['mapping'] ?? []) as $name => $mapping) {
            if (isset($response['output'][$name])) {
                $value = $response['output'][$name];
                $response['output'][$mapping] = $value;
                unset($response['output'][$name]);
            }
        }

        $this->getLogger()->info("Response", ['response' => $response]);

        return $response;
    }
}
