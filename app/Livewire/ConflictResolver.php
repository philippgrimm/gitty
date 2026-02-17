<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\ConflictService;
use App\Services\Git\GitErrorHandler;
use Livewire\Attributes\On;
use Livewire\Component;

class ConflictResolver extends Component
{
    public string $repoPath;

    public array $conflictFiles = [];

    public ?string $selectedFile = null;

    public string $oursContent = '';

    public string $theirsContent = '';

    public string $baseContent = '';

    public string $resultContent = '';

    public bool $showAbortModal = false;

    public bool $isBinary = false;

    public bool $isInMergeState = false;

    public string $mergeHeadBranch = '';

    public function mount(string $repoPath): void
    {
        $this->repoPath = $repoPath;
        $this->checkMergeState();
    }

    #[On('status-updated')]
    public function handleStatusUpdated(): void
    {
        $this->checkMergeState();
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->checkMergeState();
    }

    #[On('palette-abort-merge')]
    public function handlePaletteAbortMerge(): void
    {
        $this->showAbortModal = true;
    }

    public function selectFile(string $path): void
    {
        try {
            $this->selectedFile = $path;

            $conflictService = new ConflictService($this->repoPath);
            $conflictFile = $conflictService->getConflictVersions($path);

            $this->oursContent = $conflictFile->oursContent;
            $this->theirsContent = $conflictFile->theirsContent;
            $this->baseContent = $conflictFile->baseContent;
            $this->resultContent = $conflictFile->oursContent;
            $this->isBinary = $conflictFile->isBinary;
        } catch (\Exception $e) {
            $error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $error, type: 'error', persistent: false);
        }
    }

    public function acceptOurs(): void
    {
        $this->resultContent = $this->oursContent;
    }

    public function acceptTheirs(): void
    {
        $this->resultContent = $this->theirsContent;
    }

    public function acceptBoth(): void
    {
        $this->resultContent = $this->oursContent."\n".$this->theirsContent;
    }

    public function resolveFile(): void
    {
        if ($this->selectedFile === null) {
            return;
        }

        try {
            $conflictService = new ConflictService($this->repoPath);
            $conflictService->resolveConflict($this->selectedFile, $this->resultContent);

            $this->dispatch('show-error', message: 'File resolved and staged', type: 'success', persistent: false);

            $this->refreshConflictList();

            if (empty($this->conflictFiles)) {
                $this->selectedFile = null;
                $this->oursContent = '';
                $this->theirsContent = '';
                $this->baseContent = '';
                $this->resultContent = '';
                $this->isBinary = false;
            } else {
                $firstFile = $this->conflictFiles[0]['path'] ?? null;
                if ($firstFile) {
                    $this->selectFile($firstFile);
                }
            }

            $this->dispatch('refresh-staging');
        } catch (\Exception $e) {
            $error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $error, type: 'error', persistent: false);
        }
    }

    public function abortMerge(): void
    {
        $this->showAbortModal = true;
    }

    public function confirmAbortMerge(): void
    {
        try {
            $conflictService = new ConflictService($this->repoPath);
            $conflictService->abortMerge();

            $this->showAbortModal = false;
            $this->isInMergeState = false;
            $this->conflictFiles = [];
            $this->selectedFile = null;

            $this->dispatch('show-error', message: 'Merge aborted', type: 'success', persistent: false);
            $this->dispatch('refresh-staging');
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $error, type: 'error', persistent: false);
        }
    }

    public function cancelAbortMerge(): void
    {
        $this->showAbortModal = false;
    }

    public function render()
    {
        return view('livewire.conflict-resolver');
    }

    private function checkMergeState(): void
    {
        try {
            $conflictService = new ConflictService($this->repoPath);
            $this->isInMergeState = $conflictService->isInMergeState();

            if ($this->isInMergeState) {
                $this->mergeHeadBranch = $conflictService->getMergeHeadBranch();
                $this->refreshConflictList();

                if (! empty($this->conflictFiles) && $this->selectedFile === null) {
                    $firstFile = $this->conflictFiles[0]['path'] ?? null;
                    if ($firstFile) {
                        $this->selectFile($firstFile);
                    }
                }
            } else {
                $this->conflictFiles = [];
                $this->selectedFile = null;
            }
        } catch (\Exception $e) {
            $this->isInMergeState = false;
            $this->conflictFiles = [];
        }
    }

    private function refreshConflictList(): void
    {
        $conflictService = new ConflictService($this->repoPath);
        $this->conflictFiles = $conflictService->getConflictedFiles()->toArray();
    }
}
