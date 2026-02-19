<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\CommitService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\SettingsService;
use Livewire\Attributes\On;
use Livewire\Component;

class CommitPanel extends Component
{
    public string $repoPath;

    public string $message = '';

    public bool $isAmend = false;

    public int $stagedCount = 0;

    public ?string $lastCommitMessage = null;

    public string $currentPrefill = '';

    /** @var array<int, string> */
    public array $commitHistory = [];

    public int $historyIndex = -1;

    public string $draftMessage = '';

    /** @var array<int, string> */
    public array $storedHistory = [];

    public ?string $error = null;

    public bool $showUndoConfirmation = false;

    public bool $lastCommitPushed = false;

    public function mount(): void
    {
        $gitService = new GitService($this->repoPath);
        $status = $gitService->status();
        $this->stagedCount = $status->changedFiles
            ->filter(fn ($file) => $file->indexStatus !== '.' && $file->indexStatus !== '?')
            ->count();

        $this->currentPrefill = $this->getCommitPrefill();
        $this->message = $this->currentPrefill;
        $this->loadCommitHistory();
        $this->loadStoredHistory();
    }

    public function loadCommitHistory(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $this->commitHistory = $gitService->log(10)
                ->pluck('message')
                ->map(fn (string $message) => \Illuminate\Support\Str::before($message, "\n"))
                ->values()
                ->toArray();
        } catch (\Exception) {
            $this->commitHistory = [];
        }
    }

    public function loadStoredHistory(): void
    {
        try {
            $settingsService = app(SettingsService::class);
            $this->storedHistory = $settingsService->getCommitHistory($this->repoPath);
        } catch (\Exception) {
            $this->storedHistory = [];
        }
    }

    public function cycleHistory(string $direction): void
    {
        if (empty($this->storedHistory)) {
            return;
        }

        if ($direction === 'up') {
            // Save draft on first up press
            if ($this->historyIndex === -1) {
                $this->draftMessage = $this->message;
            }

            // Move to older message
            if ($this->historyIndex < count($this->storedHistory) - 1) {
                $this->historyIndex++;
                $this->message = $this->storedHistory[$this->historyIndex];
            }
        } elseif ($direction === 'down') {
            // Move to newer message
            if ($this->historyIndex > 0) {
                $this->historyIndex--;
                $this->message = $this->storedHistory[$this->historyIndex];
            } elseif ($this->historyIndex === 0) {
                // Return to draft
                $this->historyIndex = -1;
                $this->message = $this->draftMessage;
            }
        }
    }

    public function selectHistoryMessage(string $message): void
    {
        $this->message = $message;
        $this->historyIndex = -1;
    }

    #[On('status-updated')]
    public function refreshStagedCount(int $stagedCount = 0, array $aheadBehind = []): void
    {
        $this->stagedCount = $stagedCount;

        $newPrefill = $this->getCommitPrefill();
        if ($this->message === '' || $this->message === $this->currentPrefill) {
            $this->currentPrefill = $newPrefill;
            $this->message = $newPrefill;
        } else {
            $this->currentPrefill = $newPrefill;
        }

        $this->loadCommitHistory();
    }

    #[On('keyboard-commit')]
    public function handleKeyboardCommit(): void
    {
        $this->commit();
    }

    #[On('keyboard-commit-push')]
    public function handleKeyboardCommitPush(): void
    {
        $this->commitAndPush();
    }

    public function commit(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $this->error = null;

        try {
            $commitService = new CommitService($this->repoPath);
            $messageToCommit = $this->message;

            if ($this->isAmend) {
                $commitService->commitAmend($messageToCommit);
            } else {
                $commitService->commit($messageToCommit);
            }

            // Save to history (non-critical, don't abort commit on failure)
            try {
                $settingsService = app(SettingsService::class);
                $settingsService->addCommitMessage($this->repoPath, $messageToCommit);
            } catch (\Exception) {
                // Settings table may not exist in test environment
            }

            $this->isAmend = false;
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
            $this->historyIndex = -1;
            $this->loadCommitHistory();
            $this->loadStoredHistory();
            $this->dispatch('committed');
            $this->dispatch('prefill-updated');

            try {
                $gitService = new GitService($this->repoPath);
                $status = $gitService->status();
                $aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
            } catch (\Exception) {
                $aheadBehind = ['ahead' => 0, 'behind' => 0];
            }

            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function commitAndPush(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $this->error = null;

        try {
            $commitService = new CommitService($this->repoPath);
            $messageToCommit = $this->message;
            $commitService->commitAndPush($messageToCommit);

            // Save to history (non-critical)
            try {
                $settingsService = app(SettingsService::class);
                $settingsService->addCommitMessage($this->repoPath, $messageToCommit);
            } catch (\Exception) {
                // Settings table may not exist in test environment
            }

            $this->isAmend = false;
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
            $this->historyIndex = -1;
            $this->loadCommitHistory();
            $this->loadStoredHistory();
            $this->dispatch('committed');
            $this->dispatch('prefill-updated');

            try {
                $gitService = new GitService($this->repoPath);
                $status = $gitService->status();
                $aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
            } catch (\Exception) {
                $aheadBehind = ['ahead' => 0, 'behind' => 0];
            }

            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function toggleAmend(): void
    {
        $this->isAmend = ! $this->isAmend;

        if ($this->isAmend) {
            $commitService = new CommitService($this->repoPath);
            $this->message = $commitService->lastCommitMessage();
        } else {
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
        }
    }

    public function getCommitPrefill(): string
    {
        try {
            $gitService = new GitService($this->repoPath);
            $branch = $gitService->currentBranch();

            if (preg_match('/^(feature|bugfix)\/([A-Z]+-\d+)/', $branch, $matches)) {
                $type = $matches[1] === 'feature' ? 'feat' : 'fix';
                $ticket = $matches[2];

                return "{$type}({$ticket}): ";
            }
        } catch (\Exception) {
            // Graceful fallback for detached HEAD, empty repo, etc.
        }

        return '';
    }

    /**
     * @return array<int, array{type: string, label: string, prefix: string, description: string}>
     */
    public function getTemplates(): array
    {
        $templates = [
            ['type' => 'feat', 'label' => 'Feature', 'prefix' => 'feat: ', 'description' => 'A new feature'],
            ['type' => 'fix', 'label' => 'Bug Fix', 'prefix' => 'fix: ', 'description' => 'A bug fix'],
            ['type' => 'refactor', 'label' => 'Refactor', 'prefix' => 'refactor: ', 'description' => 'Code restructuring'],
            ['type' => 'docs', 'label' => 'Documentation', 'prefix' => 'docs: ', 'description' => 'Documentation changes'],
            ['type' => 'test', 'label' => 'Test', 'prefix' => 'test: ', 'description' => 'Adding or updating tests'],
            ['type' => 'chore', 'label' => 'Chore', 'prefix' => 'chore: ', 'description' => 'Maintenance tasks'],
            ['type' => 'style', 'label' => 'Style', 'prefix' => 'style: ', 'description' => 'Code style/formatting'],
            ['type' => 'perf', 'label' => 'Performance', 'prefix' => 'perf: ', 'description' => 'Performance improvement'],
            ['type' => 'ci', 'label' => 'CI', 'prefix' => 'ci: ', 'description' => 'CI/CD changes'],
            ['type' => 'build', 'label' => 'Build', 'prefix' => 'build: ', 'description' => 'Build system changes'],
        ];

        // Check for custom templates
        $customTemplate = $this->loadCustomTemplate();
        if ($customTemplate) {
            array_unshift($templates, [
                'type' => 'custom',
                'label' => 'Custom Template',
                'prefix' => $customTemplate,
                'description' => 'From .gitmessage',
            ]);
        }

        return $templates;
    }

    private function loadCustomTemplate(): ?string
    {
        // Check repo-level .gitmessage
        $repoTemplate = $this->repoPath.'/.gitmessage';
        if (file_exists($repoTemplate)) {
            return trim(file_get_contents($repoTemplate));
        }

        // Check git config for commit.template
        $gitService = new GitService($this->repoPath);
        $templatePath = $gitService->getConfigValue('commit.template');
        if ($templatePath) {
            $path = $templatePath;
            // Expand ~ to home directory
            $path = str_replace('~', $_SERVER['HOME'] ?? '', $path);
            if (file_exists($path)) {
                return trim(file_get_contents($path));
            }
        }

        return null;
    }

    public function applyTemplate(string $prefix): void
    {
        // If message is empty or just a prefill, replace with template prefix
        if (empty(trim($this->message)) || $this->message === $this->currentPrefill) {
            $this->message = $prefix;
        } elseif (preg_match('/^(feat|fix|refactor|docs|test|chore|style|perf|ci|build)(\(.*?\))?:\s*/', $this->message, $matches)) {
            // Replace existing template prefix with the new one, keeping the rest of the message
            $this->message = $prefix.substr($this->message, strlen($matches[0]));
        } else {
            // Prepend template type to existing message
            $this->message = $prefix.$this->message;
        }
    }

    #[On('palette-toggle-amend')]
    public function handlePaletteToggleAmend(): void
    {
        $this->toggleAmend();
    }

    #[On('palette-undo-last-commit')]
    public function promptUndoLastCommit(): void
    {
        $commitService = new CommitService($this->repoPath);

        if ($commitService->isLastCommitMerge()) {
            $this->dispatch('show-error', message: 'Cannot undo merge commits', type: 'error');

            return;
        }

        $this->lastCommitPushed = $commitService->isLastCommitPushed();
        $this->showUndoConfirmation = true;
    }

    public function confirmUndoLastCommit(): void
    {
        try {
            $commitService = new CommitService($this->repoPath);
            $commitService->undoLastCommit();
            $this->showUndoConfirmation = false;
            $this->message = $commitService->lastCommitMessage();
            $this->dispatch('status-updated');
            $this->dispatch('show-error', message: 'Last commit undone. Changes are now staged.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to undo commit: '.$e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.commit-panel');
    }
}
