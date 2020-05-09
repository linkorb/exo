<?php

namespace Exo\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class ActionCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('action')
            ->setDescription('Action info')
            ->addArgument(
                'fqan',
                InputArgument::OPTIONAL,
                'Fully Qualified Action Name'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exo = $this->getExo();

        $fqan = $input->getArgument('fqan');
        if (!$fqan) {
            $output->writeLn('Actions: ' . count($exo->getActions()));
            foreach ($exo->getActions() as $action) {
                $output->writeLn("  <info>" . $action->getName() . '</info> ' . $action->getDescription());
            }
            return 0;
        }

        $action = $exo->getAction($fqan);

        $output->writeLn('Action: <info>' . $fqan . '</info>');
        $output->writeLn("<comment>{$action->getDescription()}</comment>");
        // print_r($action->getInputSchema());

        // $output->writeLn('');
        // $output->writeLn("Config:");
        // foreach ($action->getPackage()->getConfigSchema()['properties'] as $name=>$data) {
        //     $output->writeLn("  <info>EXO__EXAMPLE__{$name}</info>: " . ($data['description'] ?? null));
        // }

        $output->writeLn('');
        $output->writeLn("Inputs:");
        foreach ($action->getInputSchema()['properties'] as $name=>$data) {
            $output->writeLn("  <info>{$name}</info>: " . ($data['description'] ?? null));
        }
        
        if ($action->getOutputSchema()) {
            $output->writeLn('');
            $output->writeLn("Outputs:");
            foreach ($action->getOutputSchema()['properties'] as $name=>$data) {
                $output->writeLn("  <info>{$name}</info>: " . ($data['description'] ?? null));
            }
        }
        $output->writeLn('');
        return 0;
    }
}
