<?php

namespace Exo\Worker;

use Exo\Core\Model\Exo;
use RuntimeException;

class NatsWorker implements WorkerInterface
{

    protected $exo;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $streamContextOptions;
    protected $messages = [];

    public function __construct(Exo $exo, array $options)
    {
        $this->exo = $exo;
        $this->host = $options['HOST'] ?? null;
        $this->port = $options['PORT'] ?? 4222;
        $this->username = $options['USERNAME'] ?? null;
        $this->password = $options['PASSWORD'] ?? null;
       
        if (!$this->host) {
            throw new RuntimeException("Required HOST for Nats worker not configured (correctly)");
        }
    }

    public function connect()
    {
        $this->exo->getLogger()->info("Connecting to {$this->host}:{$this->port}");
        $connectionOptions = new \Nats\ConnectionOptions();

        $connectionOptions
            ->setHost($this->host)
            ->setPort($this->port)
            ->setUser($this->username)
            ->setPass($this->password)
            ->setVerbose(true)
            ->setPedantic(true)
            // ->setStreamContext($streamContext)
        ;

        $this->client = new \Nats\Connection($connectionOptions);
        $this->client->connect();
        $this->exo->getLogger()->debug("Connected");

        $this->subscriptionId = $this->client->subscribe(
            'exo:request',
            function ($message) {
                $this->messages[] = $message;
                $size = strlen($message->getBody());
                $this->exo->getLogger()->debug("Received NATS message. Added to queue....", ['size' => $size]);
            }
        );

    }

    public function popRequest(): ?array
    {
        $this->client->wait(1); // wait for 1 message
        if (!$this->client->getStreamSocket()) {
            $this->exo->getLogger()->debug("Connection dropped... reconnecting");
            $this->client->reconnect(true); // reconnect & resubscribe
        }

        if (count($this->messages)==0) {
            return null; // no pending messages at the moment
        }

        $message = array_shift($this->messages); // pop first from the queue
        $request = json_decode(gzdecode($message->getBody()), true);
        $request['reply-to'] = $message->getSubject(); // save reply-to for response
        return $request;
    }

    public function pushResponse(array $request, array $response): void
    {
        $json = json_encode($response, JSON_UNESCAPED_SLASHES);
        $payload = gzencode($json);
        $size = strlen($payload);
        $subject = $request['reply-to'] ?? null;
        $this->exo->getLogger()->debug("Pushing NATS response" , ['subject' => $subject, "size" => $size]);
        if (!$subject) {
            $this->exo->getLogger()->error("Can't respond to messages without a reply-to value");
            throw new RuntimeException("Can't respond to messages without a reply-to value");
        }
        $this->client->publish(
            $subject,
            $payload
        );
        return;
    }

}
