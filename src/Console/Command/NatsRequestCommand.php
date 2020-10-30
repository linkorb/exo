<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class NatsRequestCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('nats-request')
            ->setDescription('Send a JSON request over NATS.')
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Filename containing request as JSON (use STDIN if not provided)'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo($input, $output);
        $inputArray = [];

        $filename = $input->getArgument('filename');
        if (!$filename) {
            $filename = "php://stdin";
        }
        $requestJson = file_get_contents($filename);
        $request = json_decode($requestJson, true);
        if (!$request) {
            throw new RuntimeException("Can't parse request JSON from " . $filename);
        }

        $streamContextOptions = [
            'ssl' => [
            ],
        ];



        $connectionOptions = new \Nats\ConnectionOptions();

        $this->host = getenv('EXO__WORKER__NATS__HOST');
        $this->port = getenv('EXO__WORKER__NATS__PORT') ?? 4222;
        $this->username = getenv('EXO__WORKER__NATS__USERNAME');
        $this->password = getenv('EXO__WORKER__NATS__PASSWORD');

        if (getenv('EXO__WORKER__NATS__SSL__VERIFY_PEER')=='false') {
            $exo->getLogger()->debug("Setting ssl.verify_peer to false");
            $streamContextOptions['ssl']['verify_peer'] = false;
        }
        $streamContext = stream_context_get_default($streamContextOptions);

        $connectionOptions
            ->setHost($this->host)
            ->setPort($this->port)
            ->setUser($this->username)
            ->setPass($this->password)
            ->setVerbose(true)
            ->setPedantic(true)
            ->setStreamContext($streamContext)
        ;

        $this->client = new \Nats\Connection($connectionOptions);
        $this->client->connect();

        $subject = 'exo:request';
        $payload = gzencode($requestJson);

        $exo->getLogger()->info("Sending NATS request", ['request' => $request]);

        $response = null;
        $out = $this->client->request(
            $subject,
            $payload,
            function ($message) use (&$response, $exo) {
                // echo "Got a response...\n";
                $responseJson = gzdecode($message->getBody());
                $response = json_decode($responseJson, true);
                if (!$response) {
                    $exo->getLogger()->error("Failed to parse response as JSON", ['responseJson' => $responseJson]);
                    throw new RuntimeException("Failed to parse response as JSON: " . $responseJson);
                }
                $exo->getLogger()->debug("Received response", ['response' => $responseJson]);
                echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
            }
        );
        return 0; // TODO: check exitcode
    }
}
