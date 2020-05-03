<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class RunCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('run')
            ->setDescription('Run an action')
            ->addArgument(
                'fqan',
                InputArgument::REQUIRED,
                'Fully Qualified Action Name'
            )
            ->addOption(
                'input',
                'i',
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED,
                'Pass key=value as input'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo();
        $inputArray = [];

        foreach ($input->getOption('input') as $pair) {
            $part = explode("=", $pair);
            if (count($part)!=2) {
                throw new RuntimeException("Invalid input key/value pair: " . $pair . " (use key=value format)");
            }
            $inputArray[$part[0]] = (string)$part[1];
        }

        $fqan = $input->getArgument('fqan');
        $request = [
            'action' => $fqan,
            'input' => $inputArray,
        ];
    

        $action = $exo->getAction($fqan);
        $response = $exo->handle($request);
        $output->writeLn(json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}
