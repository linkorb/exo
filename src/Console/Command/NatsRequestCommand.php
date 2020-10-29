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
            ->setDescription('Send a request over NATS');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo($input, $output);
        $inputArray = [];

        $stdin = file_get_contents("php://stdin");
        $request = json_decode($stdin, true);
        if (!$request) {
            throw new RuntimeException("Can't parse request JSON");
        }

        $connectionOptions = new \Nats\ConnectionOptions();

        $this->host = getenv('EXO__WORKER__NATS__HOST');
        $this->port = getenv('EXO__WORKER__NATS__PORT') ?? 4222;
        $this->username = getenv('EXO__WORKER__NATS__USERNAME');
        $this->password = getenv('EXO__WORKER__NATS__PASSWORD');

        $connectionOptions
            ->setHost($this->host)
            ->setPort($this->port)
            ->setUser($this->username)
            ->setPass($this->password)
            ->setVerbose(true)
            ->setPedantic(true)
        ;

        $this->client = new \Nats\Connection($connectionOptions);
        $this->client->connect();

        $subject = 'exo:request';
        $payload = gzencode($stdin);

        $exo->getLogger()->info("Sending NATS request", ['request' => $request]);

        $response = null;
        $out = $this->client->request(
            $subject,
            $payload,
            function ($message) use (&$response, $exo) {
                // echo "Got a response...\n";
                $json = gzdecode($message->getBody());
                $response = json_decode($json, true);
                if (!$response) {
                    $exo->getLogger()->error("Failed to parse response as JSON", ['response' => $json]);
                    throw new RuntimeException("Failed to parse response as JSON: " . $json);
                }
                $exo->getLogger()->debug("Received response", ['response' => $json]);
                echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;
            }
        );
        return 0; // TODO: check exitcode
    }
}