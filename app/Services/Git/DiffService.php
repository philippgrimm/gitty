<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\DiffFile;
use App\DTOs\DiffResult;
use App\DTOs\Hunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Spatie\ShikiPhp\Shiki;

class DiffService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function parseDiff(string $rawDiff): DiffResult
    {
        return DiffResult::fromDiffOutput($rawDiff);
    }

    public function extractHunks(DiffFile $file): Collection
    {
        return $file->hunks;
    }

    public function renderDiffHtml(DiffResult $diff, bool $isStaged = false): string
    {
        $html = '';

        foreach ($diff->files as $fileIndex => $file) {
            $html .= '<div class="diff-file">';
            $html .= '<div class="diff-file-header">';
            $html .= '<span class="diff-file-path">' . htmlspecialchars($file->getDisplayPath()) . '</span>';
            $html .= '<span class="diff-stats">+' . $file->additions . ' -' . $file->deletions . '</span>';
            $html .= '</div>';

            if ($file->isBinary) {
                $html .= '<div class="diff-binary">Binary file</div>';
            } else {
                foreach ($file->hunks as $hunkIndex => $hunk) {
                    $html .= '<div class="diff-hunk">';
                    $html .= '<div class="diff-hunk-header group">';
                    $html .= '<span class="flex-1">' . htmlspecialchars($hunk->header) . '</span>';
                    
                    // Add stage/unstage button
                    if ($isStaged) {
                        $html .= '<button wire:click="unstageHunk(' . $fileIndex . ', ' . $hunkIndex . ')" ';
                        $html .= 'class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 ';
                        $html .= 'px-3 py-1 text-xs font-bold uppercase tracking-wider ';
                        $html .= 'bg-red-900/50 hover:bg-red-900 text-red-100 border border-red-700 rounded" ';
                        $html .= 'title="Unstage this hunk">';
                        $html .= '− Unstage</button>';
                    } else {
                        $html .= '<button wire:click="stageHunk(' . $fileIndex . ', ' . $hunkIndex . ')" ';
                        $html .= 'class="hunk-action-btn opacity-0 group-hover:opacity-100 transition-opacity duration-200 ';
                        $html .= 'px-3 py-1 text-xs font-bold uppercase tracking-wider ';
                        $html .= 'bg-green-900/50 hover:bg-green-900 text-green-100 border border-green-700 rounded" ';
                        $html .= 'title="Stage this hunk">';
                        $html .= '+ Stage</button>';
                    }
                    
                    $html .= '</div>';

                    foreach ($hunk->lines as $line) {
                        $class = match ($line->type) {
                            'addition' => 'diff-line-addition',
                            'deletion' => 'diff-line-deletion',
                            default => 'diff-line-context',
                        };

                        $html .= '<div class="' . $class . '">';
                        $html .= '<span class="line-number">' . ($line->oldLineNumber ?? '') . '</span>';
                        $html .= '<span class="line-number">' . ($line->newLineNumber ?? '') . '</span>';

                        try {
                            $extension = pathinfo($file->getDisplayPath(), PATHINFO_EXTENSION);
                            $language = $this->mapExtensionToLanguage($extension);
                            $highlighted = Shiki::highlight($line->content, $language);
                            $html .= '<span class="line-content">' . $highlighted . '</span>';
                        } catch (\Exception $e) {
                            $html .= '<span class="line-content">' . htmlspecialchars($line->content) . '</span>';
                        }

                        $html .= '</div>';
                    }

                    $html .= '</div>';
                }
            }

            $html .= '</div>';
        }

        return $html;
    }

    public function stageHunk(DiffFile $file, Hunk $hunk): void
    {
        $patch = $this->generatePatch($file, $hunk);
        $process = Process::path($this->repoPath)->input($patch);
        $process->run('git apply --cached');
    }

    public function unstageHunk(DiffFile $file, Hunk $hunk): void
    {
        $patch = $this->generatePatch($file, $hunk);
        $process = Process::path($this->repoPath)->input($patch);
        $process->run('git apply --cached --reverse');
    }

    protected function generatePatch(DiffFile $file, Hunk $hunk): string
    {
        $patch = "diff --git a/{$file->oldPath} b/{$file->newPath}\n";
        $patch .= "--- a/{$file->oldPath}\n";
        $patch .= "+++ b/{$file->newPath}\n";
        $patch .= "@@ -{$hunk->oldStart},{$hunk->oldCount} +{$hunk->newStart},{$hunk->newCount} @@ {$hunk->header}\n";

        foreach ($hunk->lines as $line) {
            $prefix = match ($line->type) {
                'addition' => '+',
                'deletion' => '-',
                default => ' ',
            };
            $patch .= $prefix . $line->content . "\n";
        }

        return $patch;
    }

    protected function mapExtensionToLanguage(string $extension): string
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
