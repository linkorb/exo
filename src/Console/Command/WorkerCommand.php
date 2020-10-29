<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Exo\Core\Utils\ArrayUtils;
use PidHelper\PidHelper;

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
            ->setDescription('Run worker');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidPath = '/run/user/' . posix_getuid() . '/';
        if (posix_getuid()==0) {
            // root doesn't have a user-specific run dir
            // and /var/run is only writable by root
            $pidPath = '/var/run/';
        }

        $lock = new PidHelper($pidPath, 'exo-worker.pid');
        if (!$lock->lock()) {
            $output->writeln('<error>Other worker process running, quiting.</error>');

            return -1;
        }

        $exo = $this->getExo($input, $output);

        $workerType = getenv('EXO__WORKER__TYPE');
        $variables = getenv();
        $options = ArrayUtils::getByPrefix($variables, 'EXO__WORKER__' . strtoupper($workerType) . '__');

        $exo->getLogger()->info("Starting worker", ['workerType' => $workerType]);
        $className = 'Exo\\Worker\\' . $workerType . 'Worker';
        $adapter = new $className($exo, $options);

        $startAt = time();
        $maxRuntime = 60 * 30; // seconds
        $executionCount  = 0;
        $maxExecutionCount = 100;

        $adapter->connect();

        $running = true;
        while ($running) {
            $exo->getLogger()->debug("Running", ['executions' => $executionCount]);
            $request = $adapter->popRequest();
            if ($request) {
                $response = $exo->handle($request);
                $adapter->pushResponse($request, $response);
                $executionCount++;
            } else {
                sleep(3);
            }

            if (time() > ($startAt + $maxRuntime)) {
                $running = false;
            }
            if ($executionCount >= $maxExecutionCount) {
                $running = false;
            }
        }
        $lock->unlock();
        $exo->getLogger()->debug("Exiting", ['executions' => $executionCount]);
        return 0;
    }
}
