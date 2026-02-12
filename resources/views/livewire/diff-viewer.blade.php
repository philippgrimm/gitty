<div class="h-full flex flex-col bg-zinc-950 font-mono">
    @if($isEmpty && !$file)
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-500 text-6xl mb-4">⊘</div>
                <div class="text-zinc-400 uppercase tracking-widest text-sm">No file selected</div>
            </div>
        </div>
    @elseif($isEmpty && $file)
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-500 text-6xl mb-4">∅</div>
                <div class="text-zinc-400 uppercase tracking-widest text-sm">No changes to display</div>
            </div>
        </div>
    @elseif($isLargeFile)
        <div class="border-b border-zinc-800 p-4 bg-zinc-900">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-zinc-100 font-bold">{{ $file }}</span>
                    <flux:badge variant="solid" color="orange" class="uppercase tracking-wider">LARGE FILE</flux:badge>
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-500 text-6xl mb-4">⚠</div>
                <div class="text-zinc-400 uppercase tracking-widest text-sm">File too large (&gt;1MB) — diff skipped</div>
            </div>
        </div>
    @elseif($isBinary)
        <div class="border-b border-zinc-800 p-4 bg-zinc-900">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-zinc-100 font-bold">{{ $file }}</span>
                    <flux:badge variant="solid" color="zinc" class="uppercase tracking-wider">BINARY</flux:badge>
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="text-zinc-500 text-6xl mb-4">⬢</div>
                <div class="text-zinc-400 uppercase tracking-widest text-sm">Binary file — cannot display diff</div>
            </div>
        </div>
    @else
        <div class="border-b border-zinc-800 p-4 bg-zinc-900 sticky top-0 z-10">
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
