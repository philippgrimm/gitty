<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BlameService;
use App\Services\Git\GitErrorHandler;
use Livewire\Attributes\On;
use Livewire\Component;

class BlameView extends Component
{
    public string $repoPath;

    public ?string $file = null;

    public ?array $blameData = null;

    public string $error = '';

    public function mount(string $repoPath): void
    {
        $this->repoPath = $repoPath;
    }

    #[On('show-blame')]
    public function loadBlame(string $file): void
    {
        $this->file = $file;
        $this->error = '';
        $this->blameData = null;

        try {
            $blameService = new BlameService($this->repoPath);
            $blameLines = $blameService->blame($file);

            $this->blameData = $blameLines->map(fn ($line) => [
                'commitSha' => $line->commitSha,
                'shortSha' => substr($line->commitSha, 0, 7),
                'author' => $line->author,
                'date' => $line->date,
                'lineNumber' => $line->lineNumber,
                'content' => $line->content,
            ])->toArray();
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->file = null;
        $this->blameData = null;
        $this->error = '';
    }

    public function selectCommit(string $sha): void
    {
        $this->dispatch('commit-selected', sha: $sha);
        $this->dispatch('toggle-history-panel');
    }

    public function render()
    {
        return view('livewire.blame-view');
    }
}
