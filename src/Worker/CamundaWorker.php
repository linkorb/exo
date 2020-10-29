<?php

namespace Exo\Worker;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;
use Exo\Core\Model\Exo;
use RuntimeException;

class CamundaWorker implements WorkerInterface
{
    protected $httpFactory;
    protected $httpClient;
    protected $url;
    protected $username;
    protected $password;
    protected $workerId;
    protected $topics;

    public function __construct(Exo $exo, array $options)
    {
        $this->httpFactory = new Psr17Factory();
        $this->httpClient = new Psr18Client();
        $this->url = $options['URL'] ?? null;
        $this->username = $options['USERNAME'] ?? null;
        $this->password = $options['PASSWORD'] ?? null;
        $this->workerId = $options['WORKER_ID'] ?? 'exo-' . time();

        if (!$this->url) {
            throw new RuntimeException("Required URL for Camunda worker not configured (correctly)");
        }

        $topics = [];
        foreach ($exo->getActions() as $action) {
            $topic = [
                'topicName' => 'exo:' . $action->getName(),
                'lockDuration' => 10 * 1000,
                'variables' => [],
            ];
            $inputSchema = $action->getInputSchema();
            foreach ($inputSchema['properties'] as $k => $v) {
                $topic['variables'][] = $k;
            };
            $outputSchema = $action->getOutputSchema();
            if ($outputSchema) {
                foreach (($outputSchema['properties'] ?? []) as $k => $v) {
                    $topic['variables'][] = '>' . $k;
                };
            }
            $topics[] = $topic;
        }
        $this->topics = $topics;
        // print_r($topics); exit();
    }

    public function connect(): void
    {
        // noop on pull-based adapters
    }

    public function popRequest(): ?array
    {
        $body = [
            'workerId' => $this->workerId,
            'maxTasks' => 1,
            'usePriority' => true,
            'topics' => $this->topics
        ];

        $rows = $this->request('POST', '/external-task/fetchAndLock', $body);

        if (count($rows) > 0) {
            // print_r($rows);
            foreach ($rows as $row) {
                $request = [
                    'id' => $row['id'],
                    'action' => substr($row['topicName'], 4), // Skip `exo:` prefix
                    'input' => [],
                    'mapping' => [],
                ];

                // Load input variables and output mappings
                foreach ($row['variables'] as $name => $v) {
                    if ($name[0] == '>') {
                        $request['mapping'][substr($name, 1)] = $v['value'] ?? null;
                    } else {
                        $request['input'][$name] = $v['value'] ?? null;
                    }
                }
                // print_r($request); //exit();

                return $request;
            }
        }

        // returning without job
        return null;
    }

    public function pushResponse(array $request, array $response): void
    {
        if ($response['status'] == 'OK') {
            // success
            $body = [
                'workerId' => $this->workerId,
            ];
            if (isset($response['output'])) {
                $body['variables'] = [];
                foreach ($response['output'] as $k => $v) {
                    $variable = [
                        'type' => 'string',
                        'value' => $v,
                    ];
                    $type = 'string';
                    if (is_numeric($v)) {
                        $variable['type'] = 'integer';
                    }
                    if (is_bool($v)) {
                        $variable['type'] = 'boolean';
                    }
                    if (is_object($v) || is_array($v)) {
                        // $variable['type'] = 'object';
                        // unset($variable['type']);
                        $variable['type'] = 'object';
                        $variable['valueInfo'] = [
                            'objectTypeName' => 'java.util.LinkedHashMap',
                            'serializationDataFormat' => 'application/json',
                        ];
                        $variable['value'] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                    }
                    $body['variables'][$k] = $variable;
                }
            }
            $res = $this->request('POST', '/external-task/' . $request['id'] . '/complete', $body);
        } else {
            $errorDetails = $response['error'] ?? null;
            if (is_array($errorDetails)) {
                $errorDetails = implode(PHP_EOL, $errorDetails);
            }
            $body = [
                'workerId' => $this->workerId,
                'errorMessage' => 'Task failed to execute',
                'retries' => 0,
                'errorDetails' => $errorDetails,
                'retryTimeout' => 10 * 1000,
            ];
            // echo "Reporting failure\n";
            $res = $this->request('POST', '/external-task/' . $request['id'] . '/failure', $body);
        }
        return;
    }

    private function request(string $method, string $url, array $data = []): ?array
    {
        if ($url[0] == '/') {
            $url = $this->url . $url;
        }
        $request = $this->httpFactory->createRequest($method, $url);

        $bodyStream = $this->httpFactory->createStream(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $request = $request->withBody($bodyStream);
        $request = $request->withHeader(
            'Content-type',
            'application/json'
        );

        if ($this->username && $this->password) {
            $request = $request->withHeader(
                'Authorization',
                'Basic ' . base64_encode($this->username . ':' . $this->password)
            );
        }

        $response = $this->httpClient->sendRequest($request);

        // is the HTTP status code 2xx ?
        if (($response->getStatusCode() < 200) || ($response->getStatusCode() >= 300)) {
            print_r($response);
            switch ($response->getStatusCode()) {
                case 401:
                    throw new \RuntimeException('Unauthorized. Configure credentials if auth is enabled on this server.');
                default:
                    throw new \RuntimeException('Unexpected HTTP status code: ' . $response->getStatusCode() . (string)$response->getBody() . (string)$request->getBody());
            }
        }
        $json = (string)$response->getBody();
        $data = json_decode($json, true);
        return $data;
    }
}
