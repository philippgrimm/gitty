<div class="h-full flex flex-col bg-zinc-950 font-mono">
    <style>
    .diff-container {
        @apply bg-zinc-950 text-zinc-100 text-sm;
    }

    .diff-file {
        @apply border-b border-zinc-800;
    }

    .diff-file-header {
        @apply hidden;
    }

    .diff-hunk {
        @apply border-b border-zinc-900;
    }

    .diff-hunk-header {
        @apply bg-zinc-900 text-zinc-500 px-4 py-2 font-mono text-xs uppercase tracking-wider border-y border-zinc-800 flex items-center gap-3;
    }

    .diff-line-addition,
    .diff-line-deletion,
    .diff-line-context {
        @apply flex items-start font-mono text-xs leading-relaxed border-b border-zinc-900/50;
    }

    .diff-line-addition {
        @apply bg-green-950/30 hover:bg-green-950/50 transition-colors;
    }

    .diff-line-deletion {
        @apply bg-red-950/30 hover:bg-red-950/50 transition-colors;
    }

    .diff-line-context {
        @apply bg-zinc-950 hover:bg-zinc-900/30 transition-colors;
    }

    .diff-line-addition .line-number {
        @apply text-green-400/60;
    }

    .diff-line-deletion .line-number {
        @apply text-red-400/60;
    }

    .diff-line-context .line-number {
        @apply text-zinc-600;
    }

    .line-number {
        @apply w-12 text-right px-2 py-1 select-none shrink-0 text-xs;
    }

    .line-content {
        @apply flex-1 px-4 py-1 whitespace-pre-wrap break-all;
    }

    .diff-line-addition .line-content {
        @apply text-green-100;
    }

    .diff-line-deletion .line-content {
        @apply text-red-100;
    }

    .diff-line-context .line-content {
        @apply text-zinc-300;
    }

    .line-content pre {
        @apply m-0 p-0 bg-transparent;
    }

    .line-content code {
        @apply bg-transparent font-mono;
    }
    </style>

    @if($isEmpty && !$file)
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-600 text-6xl mb-4">⊘</div>
                <div class="text-zinc-500 uppercase tracking-widest text-sm">No file selected</div>
            </div>
        </div>
    @elseif($isEmpty && $file)
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-600 text-6xl mb-4">∅</div>
                <div class="text-zinc-500 uppercase tracking-widest text-sm">No changes to display</div>
            </div>
        </div>
    @elseif($isBinary)
        <div class="border-b-2 border-zinc-800 p-4 bg-zinc-900">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-zinc-100 font-bold">{{ $file }}</span>
                    <flux:badge variant="solid" color="zinc" class="uppercase tracking-wider">BINARY</flux:badge>
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-600 text-6xl mb-4">⬢</div>
                <div class="text-zinc-500 uppercase tracking-widest text-sm">Binary file — cannot display diff</div>
            </div>
        </div>
    @else
        <div class="border-b-2 border-zinc-800 p-4 bg-zinc-900 sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-zinc-100 font-bold">{{ $file }}</span>
                    @if($diffData)
                        <flux:badge variant="solid" color="yellow" class="uppercase tracking-wider">
                            {{ strtoupper($diffData['status']) }}
                        </flux:badge>
                    @endif
                </div>
                @if($diffData)
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-green-400 font-bold">+{{ $diffData['additions'] }}</span>
                        <span class="text-red-400 font-bold">-{{ $diffData['deletions'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex-1 overflow-auto">
            <div class="diff-container">
                {!! $renderedHtml !!}
            </div>
        </div>
    @endif
</div>
