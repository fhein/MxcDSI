<?php

namespace MxcDropshipInnocigs\Commands;

use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ImportCommand extends ShopwareCommand
{
    /*
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mxcdsi:import')
            ->setDescription('Import products from InnoCigs')
            /*  ->addArgument(
                  'filepath',
                  InputArgument::REQUIRED,
                  'Path to file to read data from.'
              )*/
            ->setHelp(<<<EOF
The <info>%command.name%</info> imports products from Innocigs.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Importing products from InnoCigs ...</info>');

        try {
            MxcDropshipInnocigs::getServices()->get(ImportClient::class)->import(true);
        } catch (Throwable $e) {
            $output->writeln('<merror>' . $e->getMessage() . '</merror>');
        }
        $output->writeln('<info>Done</info>');
    }
}