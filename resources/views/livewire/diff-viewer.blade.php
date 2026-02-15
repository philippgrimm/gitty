<div class="h-full flex flex-col bg-white font-mono">
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
        <div class="border-b border-[#ccd0da] px-4 h-10 bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] text-sm">{{ $file }}</span>
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider" style="background-color: #fe640b15; color: #fe640b">
                        LARGE FILE
                    </div>
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
        <div class="border-b border-[#ccd0da] px-4 h-10 bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] text-sm">{{ $file }}</span>
                    <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider" style="background-color: #9ca0b015; color: #9ca0b0">
                        BINARY
                    </div>
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
        <div class="border-b border-[#ccd0da] px-4 h-10 bg-white sticky top-0 z-10" style="box-shadow: var(--shadow-sm)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-[#4c4f69] text-sm">{{ $file }}</span>
                    @if($diffData)
                        @php
                            $badgeColor = match(strtoupper($diffData['status'])) {
                                'MODIFIED', 'M' => '#df8e1d',
                                'ADDED', 'A' => '#40a02b',
                                'DELETED', 'D' => '#d20f39',
                                'RENAMED', 'R' => '#084CCF',
                                default => '#9ca0b0',
                            };
                        @endphp
                        <div class="flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider" style="background-color: {{ $badgeColor }}15; color: {{ $badgeColor }}">
                            {{ strtoupper($diffData['status']) }}
                        </div>
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
