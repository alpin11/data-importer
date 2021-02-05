<?php


namespace Pimcore\Bundle\DataHubBatchImportBundle\Command;


use Pimcore\Bundle\DataHubBatchImportBundle\Processing\ImportProcessingService;
use Pimcore\Bundle\DataHubBatchImportBundle\Queue\QueueService;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SequentialProcessQueueCommand extends AbstractCommand
{

    /**
     * @var ImportProcessingService
     */
    protected $importProcessingService;

    /**
     * @var QueueService
     */
    protected $queueService;

    public function __construct(ImportProcessingService $importProcessingService, QueueService $queueService)
    {
        parent::__construct();
        $this->importProcessingService = $importProcessingService;
        $this->queueService = $queueService;
    }


    public function configure()
    {
        $this
            ->setName('datahub:batch-import:process-queue-sequential')
            ->setDescription('Processes all items of the queue that need to be executed sequential.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $itemIds = $this->queueService->getAllQueueEntryIds(ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL);
        $itemCount = count($itemIds);

        $output->writeln("Processing {$itemCount} items sequentially\n");

        $progressBar = new ProgressBar($output, $itemCount);
        $progressBar->start();

        foreach($itemIds as $id) {
            $this->importProcessingService->processQueueItem($id);
            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln("\n\nProcessed {$itemCount} items.");

        return 0;
    }

}
