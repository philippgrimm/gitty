<div class="h-full flex flex-col bg-white dark:bg-[var(--surface-0)] text-[var(--text-primary)] font-display">
    @if(!$file || !$blameData)
        <div class="flex-1 flex items-center justify-center animate-fade-in">
            <div class="text-center space-y-3">
                <x-pixelarticons-card-id class="w-16 h-16 mx-auto text-[var(--text-tertiary)] opacity-40" />
                <div class="text-[var(--text-tertiary)] uppercase tracking-wider text-xs font-medium">No file selected for blame</div>
                <div class="text-[var(--text-tertiary)] text-xs">Right-click a file or click "Blame" in the diff header</div>
            </div>
        </div>
    @else
        {{-- Header --}}
        <div class="sticky top-0 z-10 bg-white dark:bg-[var(--surface-0)] border-b border-[var(--border-default)] px-4 py-2 flex items-center justify-between" style="box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-3 min-w-0">
                <x-pixelarticons-card-id class="w-4 h-4 text-[var(--text-tertiary)] shrink-0" />
                <span class="text-sm text-[var(--text-secondary)] truncate">{{ $file }}</span>
                <span class="text-xs text-[var(--text-tertiary)]">{{ count($blameData) }} lines</span>
            </div>
            <flux:tooltip content="Close Blame">
                <flux:button 
                    @click="$dispatch('file-selected', { file: '{{ $file }}', staged: false })"
                    variant="ghost" 
                    size="xs"
                    square
                    class="text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]"
                >
                    <x-pixelarticons-close class="w-4 h-4" />
                </flux:button>
            </flux:tooltip>
        </div>

        {{-- Blame content --}}
        <div class="flex-1 overflow-auto">
            <table class="w-full text-xs leading-5">
                @php $prevSha = null; $groupIndex = 0; @endphp
                @foreach($blameData as $index => $line)
                    @php
                        $isNewGroup = $line['commitSha'] !== $prevSha;
                        if ($isNewGroup) { $groupIndex++; }
                        $isEvenGroup = $groupIndex % 2 === 0;
                        $prevSha = $line['commitSha'];
                    @endphp
                    <tr class="{{ $isEvenGroup ? 'bg-[var(--surface-0)]' : 'bg-white dark:bg-[rgba(255,255,255,0.02)]' }}">
                        {{-- Gutter: SHA, author, date --}}
                        <td class="pl-3 pr-2 py-0 whitespace-nowrap text-right border-r border-[var(--border-subtle)] select-none w-[280px] align-top">
                            @if($isNewGroup)
                                <div class="flex items-center gap-2 justify-end">
                                    <span class="text-[var(--text-tertiary)] truncate max-w-[100px]" title="{{ $line['author'] }}">{{ $line['author'] }}</span>
                                    <span class="text-[var(--text-tertiary)]">{{ $line['date'] }}</span>
                                    <button 
                                        wire:click="selectCommit('{{ $line['commitSha'] }}')"
                                        class="text-[#4040B0] hover:underline cursor-pointer"
                                        title="View commit {{ $line['shortSha'] }}"
                                    >{{ $line['shortSha'] }}</button>
                                </div>
                            @endif
                        </td>

                        {{-- Line number --}}
                        <td class="px-2 py-0 text-right text-[var(--text-tertiary)] select-none w-[40px] align-top border-r border-[var(--border-subtle)]">
                            {{ $line['lineNumber'] }}
                        </td>

                        {{-- Content --}}
                        <td class="pl-3 pr-4 py-0 whitespace-pre overflow-x-auto">
                            <span>{{ $line['content'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if($error)
        <div class="p-4 text-sm text-[var(--color-red)]">{{ $error }}</div>
    @endif
</div>
