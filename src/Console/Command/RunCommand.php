<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exo\Loader\ActionLoader;
use Exo\JsonFileLoader;

use RuntimeException;

class RunCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('run')
            ->setDescription('Run func')
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'exo.action.json filename'
            )
            ->addArgument(
                'inputFilename',
                InputArgument::OPTIONAL,
                'Input filename'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');
        $inputFilename = $input->getArgument('inputFilename');

        $actionLoader = new ActionLoader();
        $action = $actionLoader->load($filename);

        if (!$inputFilename) {
            $inputFilename = "php://stdin";
        } else {
            if (!file_exists($inputFilename)) {
                throw new RuntimeException("Input filename not found: " . $inputFilename);
            }
        }

        $stdin = file_get_contents($inputFilename);
        $input = json_decode($stdin, true);

        $action->validateConfig();
        $action->validateInput($input);

        $handlerFilename = $action->getHandlerFilename();
        $interpreter = $action->getInterpreter();
        $cwd = getcwd();
        $env = $_ENV;
        $process = new Process(array($interpreter, $handlerFilename), $cwd, $env, $stdin);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $res = $process->getOutput();

        $output->writeLn($res);

        $outputData = json_decode($res, true);

        $action->validateOutput($outputData);

        // print_r($func);
    }
}
