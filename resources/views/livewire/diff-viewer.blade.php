<div class="h-full flex flex-col bg-[#eff1f5] font-mono">
    @if($isEmpty && !$file)
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-file.svg')) !!}</div>
                <div class="text-[#9ca0b0] uppercase tracking-wider text-sm">No file selected</div>
            </div>
        </div>
    @elseif($isEmpty && $file)
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/no-diff.svg')) !!}</div>
                <div class="text-[#9ca0b0] uppercase tracking-wider text-sm">No changes to display</div>
            </div>
        </div>
    @elseif($isLargeFile)
        <div class="border-b border-[#ccd0da] p-4 bg-[#e6e9ef]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] font-semibold">{{ $file }}</span>
                    <flux:badge variant="solid" color="orange" class="uppercase tracking-wider">LARGE FILE</flux:badge>
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/large-file.svg')) !!}</div>
                <div class="text-[#9ca0b0] uppercase tracking-wider text-sm">File too large (&gt;1MB) — diff skipped</div>
            </div>
        </div>
    @elseif($isBinary)
        <div class="border-b border-[#ccd0da] p-4 bg-[#e6e9ef]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] font-semibold">{{ $file }}</span>
                    <flux:badge variant="solid" color="zinc" class="uppercase tracking-wider">BINARY</flux:badge>
                </div>
            </div>
        </div>
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 opacity-60">{!! file_get_contents(resource_path('svg/empty-states/binary-file.svg')) !!}</div>
                <div class="text-[#9ca0b0] uppercase tracking-wider text-sm">Binary file — cannot display diff</div>
            </div>
        </div>
    @else
        <div class="border-b border-[#ccd0da] p-4 bg-[#e6e9ef] sticky top-0 z-10" style="box-shadow: var(--shadow-sm)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] font-semibold">{{ $file }}</span>
                    @if($diffData)
                        <flux:badge variant="solid" color="yellow" class="uppercase tracking-wider">
                            {{ strtoupper($diffData['status']) }}
                        </flux:badge>
                    @endif
                </div>
                @if($diffData)
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-[#40a02b] font-bold">+{{ $diffData['additions'] }}</span>
                        <span class="text-[#d20f39] font-bold">-{{ $diffData['deletions'] }}</span>
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
