<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\DiffService;
use App\Services\Git\GitService;
use Livewire\Attributes\On;
use Livewire\Component;

class DiffViewer extends Component
{
    public string $repoPath;

    public ?string $file = null;

    public bool $isStaged = false;

    public ?array $diffData = null;

    public string $renderedHtml = '';

    public bool $isEmpty = true;

    public bool $isBinary = false;

    public function mount(): void
    {
        $this->isEmpty = true;
        $this->isBinary = false;
    }

    #[On('file-selected')]
    public function onFileSelected(string $file, bool $staged): void
    {
        $this->loadDiff($file, $staged);
    }

    public function loadDiff(string $file, bool $staged): void
    {
        $this->file = $file;
        $this->isStaged = $staged;

        $gitService = new GitService($this->repoPath);
        $diffResult = $gitService->diff($file, $staged);

        if ($diffResult->files->isEmpty()) {
            $this->isEmpty = true;
            $this->isBinary = false;
            $this->diffData = null;
            $this->renderedHtml = '';

            return;
        }

        $diffFile = $diffResult->files->first();
        $this->isEmpty = false;
        $this->isBinary = $diffFile->isBinary;

        $this->diffData = [
            'oldPath' => $diffFile->oldPath,
            'newPath' => $diffFile->newPath,
            'status' => $diffFile->status,
            'additions' => $diffFile->additions,
            'deletions' => $diffFile->deletions,
            'isBinary' => $diffFile->isBinary,
        ];

        if (! $diffFile->isBinary) {
            $diffService = new DiffService($this->repoPath);
            $this->renderedHtml = $diffService->renderDiffHtml($diffResult);
        }
    }

    public function render()
    {
        return view('livewire.diff-viewer');
    }
}
