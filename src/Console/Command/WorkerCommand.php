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
        $exo = $this->getExo($input, $output);

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
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
        } else {
            $exo->getLogger()->debug("Skipping PID locking. Not supported on windows");
        }


        $reportingUrl = getenv('EXO_REPORTING_URL');
        $workerType = getenv('EXO__WORKER__TYPE');
        $variables = getenv();
        $options = ArrayUtils::getByPrefix($variables, 'EXO__WORKER__' . strtoupper($workerType) . '__');

        $exo->getLogger()->info("Starting worker", ['workerType' => $workerType, 'exoId' => $exo->getId()]);
        $className = 'Exo\\Worker\\' . $workerType . 'Worker';
        $adapter = new $className($exo, $options);

        $startAt = time();
        $maxRuntime = 60 * 30; // seconds
        $executionCount  = 0;
        $maxExecutionCount = 100;

        $adapter->connect();

        $running = true;
        $loops = 0;
        while ($running) {
            $exo->getLogger()->debug("Running", ['executions' => $executionCount, 'loops' => $loops]);
            if ($reportingUrl) {
                // report "heartbeat"
                $url = $reportingUrl . '/heartbeat?executions=' . $executionCount;
                $res = file_get_contents($url);
            }
            $request = $adapter->popRequest();
            if ($request) {
                if ($reportingUrl) {
                    // report the request action
                    $url = $reportingUrl . '/info?request=' . urlencode(json_encode($request, JSON_UNESCAPED_SLASHES)) . '&message=' . ($request['action'] ?? '?');
                    $res = file_get_contents($url);
                }
                $response = $exo->handle($request);
                $adapter->pushResponse($request, $response);
                $executionCount++;
            } else {
                $seconds = 3;
                $exo->getLogger()->debug("Empty request. Sleeping.", ['seconds' => $seconds]);
                sleep($seconds);
            }

            if (time() > ($startAt + $maxRuntime)) {
                $running = false;
            }
            if ($executionCount >= $maxExecutionCount) {
                $running = false;
            }
            $loops++;
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $lock->unlock();
        }
        $code = 0;
        $exo->getLogger()->info("Exiting", ['executions' => $executionCount, 'code' => $code]);
        return $code;
    }
}
