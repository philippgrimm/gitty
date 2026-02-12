<div 
    wire:poll.5s.visible="refreshBranches"
    class="flex items-center gap-2 font-mono"
>
    @if($isDetachedHead)
        <div class="flex items-center gap-2 px-3 py-1.5 bg-orange-950 border border-orange-800 rounded text-orange-200">
            <span class="text-xs uppercase tracking-wider font-bold">HEAD detached at {{ substr($currentBranch, 0, 7) }}</span>
            <flux:button 
                @click="$wire.showCreateModal = true"
                variant="ghost" 
                size="xs"
                class="text-xs uppercase tracking-wider"
            >
                Create branch here
            </flux:button>
        </div>
    @else
        <flux:dropdown position="bottom-start">
            <flux:button 
                variant="ghost" 
                size="sm"
                class="flex items-center gap-2 px-3 py-1.5 bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors"
            >
                <span class="text-zinc-400">⎇</span>
                <span class="font-bold text-zinc-100">{{ $currentBranch }}</span>
                @if($aheadBehind['ahead'] > 0 || $aheadBehind['behind'] > 0)
                    <div class="flex items-center gap-1">
                        @if($aheadBehind['ahead'] > 0)
                            <flux:badge variant="solid" color="green" class="font-mono text-xs px-1 py-0">
                                ↑{{ $aheadBehind['ahead'] }}
                            </flux:badge>
                        @endif
                        @if($aheadBehind['behind'] > 0)
                            <flux:badge variant="solid" color="red" class="font-mono text-xs px-1 py-0">
                                ↓{{ $aheadBehind['behind'] }}
                            </flux:badge>
                        @endif
                    </div>
                @endif
                <span class="text-zinc-500">▼</span>
            </flux:button>

            <flux:menu class="w-96 max-h-[600px] overflow-hidden">
                <div class="flex flex-col h-full">
                <div class="p-3 border-b border-zinc-800 bg-zinc-900 sticky top-0 z-10">
                    <flux:input 
                        wire:model.live="branchQuery"
                        placeholder="Search branches..."
                        class="font-mono text-sm"
                    />
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-3 py-2 bg-zinc-900 border-b border-zinc-800 sticky top-0">
                        <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Local Branches</div>
                    </div>

                    @forelse($this->filteredLocalBranches as $branch)
                        <div class="group flex items-center justify-between gap-3 px-3 py-2 hover:bg-zinc-900 transition-colors">
                            <div 
                                wire:click="switchBranch('{{ $branch['name'] }}')"
                                class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer"
                            >
                                <div class="w-4 h-4 flex items-center justify-center">
                                    @if($branch['isCurrent'])
                                        <span class="text-green-400 text-sm">✓</span>
                                    @endif
                                </div>
                                <div class="text-sm truncate {{ $branch['isCurrent'] ? 'text-white font-bold' : 'text-zinc-300' }}">
                                    {{ $branch['name'] }}
                                </div>
                            </div>

                            @if(!$branch['isCurrent'])
                                <flux:dropdown position="left">
                                    <flux:button 
                                        icon="ellipsis-horizontal"
                                        variant="ghost" 
                                        size="xs"
                                        square
                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                    />
                                    <flux:menu>
                                        <flux:menu.item wire:click="switchBranch('{{ $branch['name'] }}')" icon="arrow-path">
                                            Switch to
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="mergeBranch('{{ $branch['name'] }}')" icon="arrow-down-tray">
                                            Merge into current
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="deleteBranch('{{ $branch['name'] }}')" variant="danger" icon="trash">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </div>
                    @empty
                        <div class="px-3 py-4 text-center text-zinc-500 text-sm">No local branches found</div>
                    @endforelse

                    @if($this->filteredRemoteBranches->isNotEmpty())
                        <div class="px-3 py-2 bg-zinc-900 border-t-2 border-zinc-800 sticky top-0">
                            <div class="text-xs uppercase tracking-widest font-bold text-zinc-500">Remote Branches</div>
                        </div>

                        @foreach($this->filteredRemoteBranches as $branch)
                            @php
                                $cleanName = str_replace('remotes/', '', $branch['name']);
                            @endphp
                            <div class="px-3 py-2 hover:bg-zinc-900 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 h-4"></div>
                                    <div class="text-sm truncate text-zinc-500 italic">
                                        {{ $cleanName }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="border-t border-zinc-800 p-2 bg-zinc-900 sticky bottom-0">
                    <flux:button 
                        @click="$wire.showCreateModal = true"
                        variant="primary" 
                        size="sm"
                        icon="plus"
                        class="w-full uppercase tracking-wider text-xs"
                    >
                        New Branch
                    </flux:button>
                </div>
                </div>
            </flux:menu>
        </flux:dropdown>
    @endif

    <flux:modal wire:model="showCreateModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Create New Branch</flux:heading>
            <flux:subheading class="font-mono">
                Create a new branch from an existing branch
            </flux:subheading>
        </div>

        <flux:input 
            wire:model="newBranchName"
            label="Branch name"
            placeholder="feature/my-feature"
            class="font-mono"
        />

        <div>
            <flux:field>
                <flux:label class="font-mono">Base branch</flux:label>
                <flux:select wire:model="baseBranch">
                    @foreach($this->filteredLocalBranches as $branch)
                        <flux:select.option value="{{ $branch['name'] }}">{{ $branch['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button 
                variant="primary" 
                wire:click="createBranch"
                :disabled="empty(trim($newBranchName))"
                class="uppercase tracking-wider"
            >
                Create Branch
            </flux:button>
        </div>
    </flux:modal>
</div>
