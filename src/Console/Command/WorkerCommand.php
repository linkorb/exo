<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Exo\Core\Utils\ArrayUtils;

class WorkerCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('worker')
            ->setDescription('Run worker')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo();

        $workerName = 'camunda';

        $variables = getenv();
        $options = ArrayUtils::getByPrefix($variables, 'EXO__WORKER__');

        $className = 'Exo\\Worker\\' . $options['TYPE'] . 'Worker';
        $adapter = new $className($exo, $options);

        $running = true;
        while ($running) {
            echo "Running" . PHP_EOL;
            $request = $adapter->popRequest();
            if ($request) {
                
                $actionName = $request['action'] ?? null;
                $action = $exo->getAction($actionName);
                $package = $action->getPackage();
                $response = $exo->handle($request);
                echo (json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL);
                $adapter->pushResponse($response);
            } else {
                sleep(3);
            }
        }
    }
}
