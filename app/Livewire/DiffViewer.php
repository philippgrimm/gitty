<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\DiffService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Livewire\Component;

class DiffViewer extends Component
{
    public string $repoPath;

    public ?string $file = null;

    public bool $isStaged = false;

    public ?array $diffData = null;

    public ?array $files = null;

    public bool $isEmpty = true;

    public bool $isBinary = false;

    public bool $isLargeFile = false;

    public string $error = '';

    public function mount(): void
    {
        $this->isEmpty = true;
        $this->isBinary = false;
        $this->isLargeFile = false;
        $this->files = null;
        $this->error = '';
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
        $this->error = '';

        try {
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

        try {
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

            $hunkLines = collect($hunkData['lines'])->map(function ($line) {
                return new \App\DTOs\HunkLine(
                    type: $line['type'],
                    content: $line['content'],
                    oldLineNumber: $line['oldLineNumber'],
                    newLineNumber: $line['newLineNumber'],
                );
            });

            $hunk = new \App\DTOs\Hunk(
                oldStart: $hunkData['oldStart'],
                oldCount: $hunkData['oldCount'],
                newStart: $hunkData['newStart'],
                newCount: $hunkData['newCount'],
                header: $hunkData['header'],
                lines: $hunkLines,
            );

            $diffService = new DiffService($this->repoPath);
            $diffService->stageHunk($diffFile, $hunk);

            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function unstageHunk(int $fileIndex, int $hunkIndex): void
    {
        if (! $this->files || ! isset($this->files[$fileIndex]['hunks'][$hunkIndex])) {
            return;
        }

        try {
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

            $hunkLines = collect($hunkData['lines'])->map(function ($line) {
                return new \App\DTOs\HunkLine(
                    type: $line['type'],
                    content: $line['content'],
                    oldLineNumber: $line['oldLineNumber'],
                    newLineNumber: $line['newLineNumber'],
                );
            });

            $hunk = new \App\DTOs\Hunk(
                oldStart: $hunkData['oldStart'],
                oldCount: $hunkData['oldCount'],
                newStart: $hunkData['newStart'],
                newCount: $hunkData['newCount'],
                header: $hunkData['header'],
                lines: $hunkLines,
            );

            $diffService = new DiffService($this->repoPath);
            $diffService->unstageHunk($diffFile, $hunk);

            $this->refreshFileData($fileIndex);
            $this->dispatch('refresh-staging');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function render()
    {
        return view('livewire.diff-viewer');
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
        $result = Process::path($this->repoPath)->run("git cat-file -s HEAD:\"{$file}\" 2>/dev/null || echo 0");

        return (int) trim($result->output());
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
}
