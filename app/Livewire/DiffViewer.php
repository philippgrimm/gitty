<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HandlesGitOperations;
use App\Services\EditorService;
use App\Services\Git\DiffService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\SettingsService;
use Livewire\Attributes\On;
use Livewire\Component;

class DiffViewer extends Component
{
    use HandlesGitOperations;

    public string $repoPath;

    public ?string $file = null;

    public bool $isStaged = false;

    public ?array $diffData = null;

    public ?array $files = null;

    public bool $isEmpty = true;

    public bool $isBinary = false;

    public bool $isLargeFile = false;

    public bool $isImage = false;

    public ?array $imageData = null;

    public string $error = '';

    public string $diffViewMode = 'unified';

    public function mount(): void
    {
        $this->isEmpty = true;
        $this->isBinary = false;
        $this->isLargeFile = false;
        $this->isImage = false;
        $this->imageData = null;
        $this->files = null;
        $this->error = '';
    }

    public function toggleDiffViewMode(): void
    {
        $this->diffViewMode = $this->diffViewMode === 'unified' ? 'split' : 'unified';
    }

    #[On('palette-toggle-diff-view')]
    public function handlePaletteToggleDiffView(): void
    {
        $this->toggleDiffViewMode();
    }

    public function openInEditor(?int $line = null): void
    {
        if ($this->file === null) {
            return;
        }

        try {
            $editorService = new EditorService(new SettingsService);
            $editorService->openFile($this->repoPath, $this->file, $line ?? 1);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'No editor configured or detected', type: 'error', persistent: false);
        }
    }

    #[On('palette-open-in-editor')]
    public function handlePaletteOpenInEditor(): void
    {
        $this->openInEditor();
    }

    public function showBlame(): void
    {
        if ($this->file === null) {
            return;
        }

        $this->dispatch('show-blame', file: $this->file);
    }

    #[On('file-selected')]
    public function onFileSelected(string $file, bool $staged): void
    {
        $this->loadDiff($file, $staged);
    }

    #[On('committed')]
    #[On('status-updated')]
    public function clearIfFileNoLongerChanged(int $stagedCount = 0, array $aheadBehind = []): void
    {
        if ($this->file === null) {
            return;
        }

        // Re-check if the currently displayed file still has changes
        try {
            $gitService = new GitService($this->repoPath);
            $diffResult = $gitService->diff($this->file, $this->isStaged);

            if ($diffResult->files->isEmpty()) {
                $this->clearDiff();
            }
        } catch (\Exception) {
            $this->clearDiff();
        }
    }

    public function clearDiff(): void
    {
        $this->file = null;
        $this->isStaged = false;
        $this->diffData = null;
        $this->files = null;
        $this->isEmpty = true;
        $this->isBinary = false;
        $this->isLargeFile = false;
        $this->isImage = false;
        $this->imageData = null;
        $this->error = '';
    }

    public function loadDiff(string $file, bool $staged): void
    {
        $this->file = $file;
        $this->isStaged = $staged;
        $this->error = '';

        try {
            // Check if image file BEFORE normal diff parsing
            if ($this->isImageFile($file)) {
                $this->isImage = true;
                $this->isEmpty = false;
                $this->isBinary = false;
                $this->isLargeFile = false;
                $this->imageData = $this->getImageData($file);
                $this->diffData = null;
                $this->files = null;

                return;
            }

            $this->isImage = false;
            $this->imageData = null;

            $fileSize = $this->getFileSize($file);
            if ($fileSize > 1048576) {
                $this->isLargeFile = true;
                $this->isEmpty = false;
                $this->isBinary = false;
                $this->diffData = null;
                $this->files = null;

                return;
            }

            $this->isLargeFile = false;

            $gitService = new GitService($this->repoPath);
            $diffResult = $gitService->diff($file, $staged);

            if ($diffResult->files->isEmpty()) {
                $this->isEmpty = true;
                $this->isBinary = false;
                $this->diffData = null;
                $this->files = null;

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

            $this->files = $diffResult->files->map(function ($file) {
                $extension = pathinfo($file->getDisplayPath(), PATHINFO_EXTENSION);
                $language = $this->mapExtensionToLanguage($extension);

                return [
                    'oldPath' => $file->oldPath,
                    'newPath' => $file->newPath,
                    'status' => $file->status,
                    'isBinary' => $file->isBinary,
                    'additions' => $file->additions,
                    'deletions' => $file->deletions,
                    'language' => $language,
                    'hunks' => $file->hunks->map(function ($hunk) {
                        return [
                            'oldStart' => $hunk->oldStart,
                            'oldCount' => $hunk->oldCount,
                            'newStart' => $hunk->newStart,
                            'newCount' => $hunk->newCount,
                            'header' => $hunk->header,
                            'lines' => $hunk->lines->map(function ($line) {
                                return [
                                    'type' => $line->type,
                                    'content' => $line->content,
                                    'oldLineNumber' => $line->oldLineNumber,
                                    'newLineNumber' => $line->newLineNumber,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isEmpty = true;
            $this->isBinary = false;
            $this->isLargeFile = false;
        }
    }

    public function stageHunk(int $fileIndex, int $hunkIndex): void
    {
        if (! $this->files || ! isset($this->files[$fileIndex]['hunks'][$hunkIndex])) {
            return;
        }

        $this->executeGitOperation(function () use ($fileIndex, $hunkIndex) {
            [$diffFile, $hunk] = $this->hydrateDiffFileAndHunk($fileIndex, $hunkIndex);
            $diffService = new DiffService($this->repoPath);
            $diffService->stageHunk($diffFile, $hunk);
            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        }, dispatchStatusUpdate: false);
    }

    public function unstageHunk(int $fileIndex, int $hunkIndex): void
    {
        if (! $this->files || ! isset($this->files[$fileIndex]['hunks'][$hunkIndex])) {
            return;
        }

        $this->executeGitOperation(function () use ($fileIndex, $hunkIndex) {
            [$diffFile, $hunk] = $this->hydrateDiffFileAndHunk($fileIndex, $hunkIndex);
            $diffService = new DiffService($this->repoPath);
            $diffService->unstageHunk($diffFile, $hunk);
            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        }, dispatchStatusUpdate: false);
    }

    public function stageSelectedLines(int $fileIndex, int $hunkIndex, array $lineIndices): void
    {
        if (! $this->files || ! isset($this->files[$fileIndex]['hunks'][$hunkIndex])) {
            return;
        }

        $this->executeGitOperation(function () use ($fileIndex, $hunkIndex, $lineIndices) {
            [$diffFile, $hunk] = $this->hydrateDiffFileAndHunk($fileIndex, $hunkIndex);
            $diffService = new DiffService($this->repoPath);
            $diffService->stageLines($diffFile, $hunk, $lineIndices);
            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        }, dispatchStatusUpdate: false);
    }

    public function unstageSelectedLines(int $fileIndex, int $hunkIndex, array $lineIndices): void
    {
        if (! $this->files || ! isset($this->files[$fileIndex]['hunks'][$hunkIndex])) {
            return;
        }

        $this->executeGitOperation(function () use ($fileIndex, $hunkIndex, $lineIndices) {
            [$diffFile, $hunk] = $this->hydrateDiffFileAndHunk($fileIndex, $hunkIndex);
            $diffService = new DiffService($this->repoPath);
            $diffService->unstageLines($diffFile, $hunk, $lineIndices);
            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        }, dispatchStatusUpdate: false);
    }

    public function render()
    {
        return view('livewire.diff-viewer');
    }

    /**
     * Hydrate DiffFile and Hunk DTOs from stored array data.
     *
     * @return array{0: \App\DTOs\DiffFile, 1: \App\DTOs\Hunk}
     */
    private function hydrateDiffFileAndHunk(int $fileIndex, int $hunkIndex): array
    {
        $fileData = $this->files[$fileIndex];
        $hunkData = $fileData['hunks'][$hunkIndex];

        $diffFile = new \App\DTOs\DiffFile(
            oldPath: $fileData['oldPath'],
            newPath: $fileData['newPath'],
            status: $fileData['status'],
            isBinary: $fileData['isBinary'],
            hunks: collect(),
            additions: $fileData['additions'],
            deletions: $fileData['deletions'],
        );

        $hunkLines = collect($hunkData['lines'])->map(fn ($line) => new \App\DTOs\HunkLine(
            type: $line['type'],
            content: $line['content'],
            oldLineNumber: $line['oldLineNumber'],
            newLineNumber: $line['newLineNumber'],
        ));

        $hunk = new \App\DTOs\Hunk(
            oldStart: $hunkData['oldStart'],
            oldCount: $hunkData['oldCount'],
            newStart: $hunkData['newStart'],
            newCount: $hunkData['newCount'],
            header: $hunkData['header'],
            lines: $hunkLines,
        );

        return [$diffFile, $hunk];
    }

    private function refreshFileData(int $fileIndex): void
    {
        $gitService = new GitService($this->repoPath);
        $diffResult = $gitService->diff($this->file, $this->isStaged);

        if ($diffResult->files->isEmpty()) {
            // File fully staged/unstaged — no more hunks to show
            $this->files = null;
            $this->isEmpty = true;
            $this->diffData = null;

            return;
        }

        $diffFile = $diffResult->files->first();

        // Update diffData (header info)
        $this->diffData = [
            'oldPath' => $diffFile->oldPath,
            'newPath' => $diffFile->newPath,
            'status' => $diffFile->status,
            'additions' => $diffFile->additions,
            'deletions' => $diffFile->deletions,
            'isBinary' => $diffFile->isBinary,
        ];

        // Update only the affected file in $files array
        $extension = pathinfo($diffFile->getDisplayPath(), PATHINFO_EXTENSION);
        $language = $this->mapExtensionToLanguage($extension);

        $this->files[$fileIndex] = [
            'oldPath' => $diffFile->oldPath,
            'newPath' => $diffFile->newPath,
            'status' => $diffFile->status,
            'isBinary' => $diffFile->isBinary,
            'additions' => $diffFile->additions,
            'deletions' => $diffFile->deletions,
            'language' => $language,
            'hunks' => $diffFile->hunks->map(function ($hunk) {
                return [
                    'oldStart' => $hunk->oldStart,
                    'oldCount' => $hunk->oldCount,
                    'newStart' => $hunk->newStart,
                    'newCount' => $hunk->newCount,
                    'header' => $hunk->header,
                    'lines' => $hunk->lines->map(function ($line) {
                        return [
                            'type' => $line->type,
                            'content' => $line->content,
                            'oldLineNumber' => $line->oldLineNumber,
                            'newLineNumber' => $line->newLineNumber,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    private function getFileSize(string $file): int
    {
        try {
            $gitService = new GitService($this->repoPath);

            return $gitService->getTrackedFileSize($file);
        } catch (\Exception) {
            return 0;
        }
    }

    private function mapExtensionToLanguage(string $extension): string
    {
        return match ($extension) {
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'jsx' => 'jsx',
            'tsx' => 'tsx',
            'py' => 'python',
            'rb' => 'ruby',
            'go' => 'go',
            'rs' => 'rust',
            'java' => 'java',
            'c' => 'c',
            'cpp', 'cc', 'cxx' => 'cpp',
            'cs' => 'csharp',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'json' => 'json',
            'yaml', 'yml' => 'yaml',
            'md' => 'markdown',
            'sh', 'bash' => 'bash',
            default => 'text',
        };
    }

    public function getSplitLines(array $hunk): array
    {
        $left = [];
        $right = [];
        $pairs = [];

        foreach ($hunk['lines'] as $line) {
            if ($line['type'] === 'context') {
                $this->flushPairs($left, $right, $pairs);
                $pairs[] = ['left' => $line, 'right' => $line];
            } elseif ($line['type'] === 'deletion') {
                $left[] = $line;
            } elseif ($line['type'] === 'addition') {
                $right[] = $line;
            }
        }
        $this->flushPairs($left, $right, $pairs);

        return $pairs;
    }

    private function flushPairs(array &$left, array &$right, array &$pairs): void
    {
        $max = max(count($left), count($right));
        for ($i = 0; $i < $max; $i++) {
            $pairs[] = [
                'left' => $left[$i] ?? null,
                'right' => $right[$i] ?? null,
            ];
        }
        $left = [];
        $right = [];
    }

    private function isImageFile(string $file): bool
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp']);
    }

    private function getImageData(string $file): array
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeType = $this->getMimeType($extension);

        $oldImage = null;
        $newImage = null;
        $oldSize = 0;
        $newSize = 0;

        // Try to get old version from git (HEAD)
        try {
            $gitService = new GitService($this->repoPath);
            $oldContent = $gitService->getFileContentAtHead($file);
            if ($oldContent !== null) {
                $oldImage = 'data:'.$mimeType.';base64,'.base64_encode($oldContent);
                $oldSize = strlen($oldContent);
            }
        } catch (\Exception) {
            // Invalid repo path — no old image available
        }

        // Try to get new version from working directory
        $filePath = $this->repoPath.'/'.$file;
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $newImage = 'data:'.$mimeType.';base64,'.base64_encode($content);
            $newSize = strlen($content);
        }

        return [
            'oldImage' => $oldImage,
            'newImage' => $newImage,
            'oldSize' => $oldSize,
            'newSize' => $newSize,
            'extension' => $extension,
        ];
    }

    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'bmp' => 'image/bmp',
            default => 'application/octet-stream',
        };
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
