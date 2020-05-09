<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class RequestCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('request')
            ->setDescription('Handle a request')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo();
        $inputArray = [];

        
        $stdin = file_get_contents("php://stdin");
        $request = json_decode($stdin, true);   
        if (!$request) {
            throw new RuntimeException("Can't parse request JSON");
        }
        $fqan = $request['action'] ?? null;
        if (!$fqan) {
            throw new RuntimeException("Action undefined in request");
        }
        
        $action = $exo->getAction($fqan);
        $response = $exo->handle($request);
        $output->writeLn(json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        return 0; // TODO: check exitcode
    }
}
