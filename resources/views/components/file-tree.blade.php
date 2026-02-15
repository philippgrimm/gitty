@props(['tree', 'staged' => false, 'level' => 0])

<div>
    @foreach($tree as $node)
        @if($node['type'] === 'directory')
            <div 
                wire:key="dir-{{ $node['path'] }}"
                x-data="{ expanded: true }"
            >
                <div 
                    @click="expanded = !expanded"
                    class="group px-4 py-1.5 hover:bg-[#eff1f5] cursor-pointer transition-colors flex items-center gap-2"
                    style="padding-left: {{ ($level * 16) + 16 }}px"
                >
                    <div 
                        class="text-[#9ca0b0] transition-transform duration-200"
                        :class="expanded ? 'rotate-90' : ''"
                    >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <x-phosphor-folder-simple class="w-3.5 h-3.5 text-[#9ca0b0]" />
                    <div class="text-sm font-medium text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                        {{ $node['name'] }}
                    </div>
                    <span class="text-xs text-[#9ca0b0] ml-1">{{ count($node['children']) }}</span>
                </div>
                
                <div x-show="expanded" x-collapse>
                    <x-file-tree :tree="$node['children']" :staged="$staged" :level="$level + 1" />
                </div>
            </div>
        @else
            <div 
                wire:key="file-{{ $node['path'] }}-{{ $staged ? 'staged' : 'unstaged' }}"
                wire:click="selectFile('{{ $node['path'] }}', {{ $staged ? 'true' : 'false' }})"
                class="group px-4 py-1.5 hover:bg-[#eff1f5] cursor-pointer transition-colors duration-150 flex items-center gap-3 relative"
                style="padding-left: {{ ($level * 16) + 16 }}px"
            >
                <div class="flex items-center gap-2.5 flex-1 min-w-0">
                    @php
                        $status = $staged ? $node['indexStatus'] : ($node['worktreeStatus'] ?? $node['indexStatus']);
                        $statusConfig = match($status) {
                            'M' => ['label' => 'M', 'color' => 'yellow', 'icon' => '●'],
                            'A' => ['label' => 'A', 'color' => 'green', 'icon' => '+'],
                            'D' => ['label' => 'D', 'color' => 'red', 'icon' => '−'],
                            'R' => ['label' => 'R', 'color' => 'blue', 'icon' => '→'],
                            'U' => ['label' => 'U', 'color' => 'orange', 'icon' => 'U'],
                            '?' => ['label' => 'U', 'color' => 'green', 'icon' => '?'],
                            default => ['label' => '?', 'color' => 'zinc', 'icon' => '?'],
                        };
                    @endphp
                    <div class="w-2 h-2 rounded-full shrink-0 {{ match($statusConfig['color']) { 'yellow' => 'bg-[#df8e1d]', 'green' => 'bg-[#40a02b]', 'red' => 'bg-[#d20f39]', 'blue' => 'bg-[#084CCF]', 'orange' => 'bg-[#fe640b]', default => 'bg-[#9ca0b0]' } }}"></div>
                    <flux:tooltip :content="$node['path']" class="min-w-0">
                        <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
                            {{ $node['name'] }}
                        </div>
                    </flux:tooltip>
                </div>
                
                @if($staged)
                    <div class="absolute right-0 inset-y-0 hidden group-hover:flex items-center pr-4 pl-2 bg-[#eff1f5]">
                        <flux:button 
                            wire:click.stop="unstageFile('{{ $node['path'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="unstageFile"
                            variant="ghost" 
                            size="xs"
                            square
                        >
                            <x-phosphor-minus class="w-3.5 h-3.5" />
                        </flux:button>
                    </div>
                @else
                    <div class="absolute right-0 inset-y-0 hidden group-hover:flex items-center gap-1 pr-4 pl-2 bg-[#eff1f5]">
                        <flux:button 
                            wire:click.stop="stageFile('{{ $node['path'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="stageFile"
                            variant="ghost" 
                            size="xs"
                            square
                        >
                            <x-phosphor-plus class="w-3.5 h-3.5" />
                        </flux:button>
                        <flux:button 
                            @click.stop="showDiscardModal = true; discardAll = false; discardTarget = '{{ $node['path'] }}'"
                            variant="ghost" 
                            size="xs"
                            square
                            class="text-[#d20f39] hover:text-[#d20f39]"
                        >
                            <x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" />
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    @endforeach
</div>
