<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\DataImporterBundle\Messenger;

use Pimcore\Bundle\DataImporterBundle\Processing\ImportProcessingService;
use Pimcore\Bundle\DataImporterBundle\Queue\QueueService;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\Messenger\MessageBusInterface;

class DataImporterHandler
{
    const IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX = 'DATA-IMPORTER::worker-count::';

    protected $workerCounts = [
        ImportProcessingService::EXECUTION_TYPE_PARALLEL => 3,
        ImportProcessingService::EXECUTION_TYPE_SEQUENTIAL => 1,
    ];

    public function __construct(
        protected QueueService $queueService,
        protected ImportProcessingService $importProcessingService,
        protected MessageBusInterface $messageBus,
        protected int $workerCountLifeTime,
        protected int $workerItemCount,
        protected $workerCountParallel
    ) {
        $this->workerCounts[ImportProcessingService::EXECUTION_TYPE_PARALLEL] = $this->workerCountParallel;
    }

    public function __invoke(DataImporterMessage $message)
    {
        foreach ($message->getIds() as $id) {
            $this->importProcessingService->processQueueItem($id);
        }

        $tmpStoreKey = self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $message->getExecutionType();
        $workerCount = TmpStore::get($tmpStoreKey)?->getData() ?? 0;
        $workerCount--;
        TmpStore::set($tmpStoreKey, $workerCount, null, $this->workerCountLifeTime);

        $this->dispatchMessages($message->getExecutionType());
    }

    public function dispatchMessages(string $executionType)
    {
        $tmpStoreKey = self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY_PREFIX . $executionType;

        $workerCount = TmpStore::get($tmpStoreKey)?->getData() ?? 0;

        $addWorkers = true;
        while ($addWorkers && $workerCount < ($this->workerCounts[$executionType] ?? 1)) {
            $ids = $this->queueService->getAllQueueEntryIds($executionType, $this->workerItemCount, true);
            if (!empty($ids)) {
                $this->messageBus->dispatch(new DataImporterMessage($executionType, $ids));
                $workerCount++;
                TmpStore::set($tmpStoreKey, $workerCount, null, $this->workerCountLifeTime);
            } else {
                $addWorkers = false;
            }
        }
    }
}
