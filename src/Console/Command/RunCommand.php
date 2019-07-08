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
        $fqan = $input->getArgument('fqan');
        $exo = $this->getExo();

        $inputArray = [];

        foreach ($input->getOption('input') as $pair) {
            $part = explode("=", $pair);
            if (count($part)!=2) {
                throw new RuntimeException("Invalid input key/value pair: " . $pair . " (use key=value format)");
            }
            $inputArray[$part[0]] = (string)$part[1];
        }

        $action = $exo->getAction($fqan);
        $package = $action->getPackage();
        $config = $exo->getPackageConfig($package->getName());

        $request = [
            'action' => $fqan,
            'config' => $config,
            'input' => $inputArray,
        ];
    
        if ($output->isVerbose()) {
            $output->writeLn("<info>Request:</info>");
            $output->writeLn(json_encode($request, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            $output->writeLn("");
        }

        $response = $exo->handle($request);

        if ($output->isVerbose()) {
            $output->writeLn("<info>Response:</info>");
            $output->writeLn(json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            $output->writeLn("");
        }
        
        $output->writeLn('<info>Status:</info> ' . $response['status']);
        $output->writeLn('<info>Output:</info>');
        foreach ($response['output'] as $k=>$v) {
            if (!is_string($v)) {
                $v = json_encode($v, JSON_UNESCAPED_SLASHES);
            }
            $output->writeLn("  * <comment>{$k}</comment>: {$v}");
        } 


        // print_r($func);
    }
}
