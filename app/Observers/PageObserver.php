<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\PageInstaller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageObserver
{
    /**
     * Per-model "before update" state. Must be static: Laravel resolves a new observer instance
     * for each eloquent event (see Illuminate\Events\Dispatcher::createClassCallable), so instance
     * properties would not carry updating → updated.
     *
     * @var array<int|string, array{page_alias: string, module_id: int}>
     */
    private static array $pageSnapshots = [];

    public function __construct(
        private PageInstaller $installer
    ) {}

    public function updating(Page $page): void
    {
        self::$pageSnapshots[$page->getKey()] = [
            'page_alias' => (string) $page->getOriginal('page_alias'),
            'module_id' => (int) $page->getOriginal('module_id'),
        ];
    }

    public function created(Page $page): void
    {
        try {
            $this->installer->install($page);
        } catch (\Throwable $e) {
            Log::error('PageInstaller failed', [
                'page_id' => $page->id,
                'alias' => $page->page_alias,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public function updated(Page $page): void
    {
        $key = $page->getKey();
        $snapshot = self::$pageSnapshots[$key] ?? null;
        unset(self::$pageSnapshots[$key]);

        if ($snapshot === null) {
            try {
                $this->installer->syncPagesRegistryLog();
            } catch (\Throwable $e) {
                Log::error('PageInstaller sync pages log failed (no snapshot)', [
                    'page_id' => $page->getKey(),
                    'message' => $e->getMessage(),
                ]);
                report($e);
            }

            return;
        }

        try {
            $this->installer->syncAfterPageUpdate($page, $snapshot);
        } catch (\Throwable $e) {
            Log::error('PageInstaller sync after update failed', [
                'page_id' => $page->id,
                'alias' => $page->page_alias,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public function deleted(Page $page): void
    {
        try {
            $page->loadMissing('module');
            $this->installer->archiveDeletedPage($page);
            $this->installer->removePageFromDisk($page);
            DB::afterCommit(fn (): mixed => $this->installer->syncPagesRegistryLog());
        } catch (\Throwable $e) {
            Log::error('PageInstaller cleanup after delete failed', [
                'page_id' => $page->id,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
