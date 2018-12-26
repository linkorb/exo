<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Exo\Loader\PackageLoader;

use RuntimeException;

class PackageCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('package')
            ->setDescription('Display package definition')
            ->addOption(
                'package',
                '-p',
                InputOption::VALUE_REQUIRED,
                'Package configuration file to load'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getOption('package');
        if (!$filename) {
            $filename = 'exo.package.json';
        }

        $packageLoader = new PackageLoader();
        $package = $packageLoader->load($filename);
        // print_r($package);
        foreach ($package->getActions() as $action) {
            $output->writeLn('<comment>' . $action->getName() . '</comment> ' . $action->getDescription());
        }
    }
}
