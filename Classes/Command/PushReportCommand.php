<?php

declare(strict_types=1);

namespace Portrino\CmsObserverClient\Command;

use Portrino\CmsObserverClient\Service\ObserverApiClient;
use Portrino\CmsObserverClient\Service\ReportBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'cms-observer:push', description: 'Push core/PHP/DB version and extension inventory to CMS Observer')]
class PushReportCommand extends Command
{
    public function __construct(
        private readonly ReportBuilder $reportBuilder,
        private readonly ObserverApiClient $apiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the report as JSON instead of sending it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $report = $this->reportBuilder->build();

        if ($input->getOption('dry-run') === true) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        try {
            $accepted = $this->apiClient->pushReport($report);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if (!$accepted) {
            $io->error('CMS Observer rejected the report.');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Reported core %s, PHP %s, %d extensions to CMS Observer.',
            $report['coreVersion'],
            $report['phpVersion'],
            count($report['extensions'])
        ));

        return Command::SUCCESS;
    }
}
