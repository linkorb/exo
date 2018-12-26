<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Exo\Loader\ServiceLoader;

use RuntimeException;

class ServiceCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('service')
            ->setDescription('Display service definition')
            ->addOption(
                'config',
                '-c',
                InputOption::VALUE_REQUIRED,
                'Config file to load'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceFilename = $input->getOption('config');
        if (!$serviceFilename) {
            $serviceFilename = 'exo.library.json';
        }

        $serviceLoader = new ServiceLoader();
        $service = $serviceLoader->load($serviceFilename);
        // print_r($serviceDefinition);
        foreach ($service->getActions() as $a) {
            $output->writeLn('<comment>' . $a->getName() . '</comment> ' . $a->getDescription());
        }
    }
}
