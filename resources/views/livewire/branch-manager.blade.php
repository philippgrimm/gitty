<div 
    wire:poll.5s.visible="refreshBranches"
    class="h-full flex flex-col bg-zinc-950 text-zinc-100 font-mono border-r-2 border-zinc-800"
>
    @if($error)
        <div class="bg-red-950 border-b-2 border-red-800 text-red-200 px-4 py-3 text-xs uppercase tracking-wider font-bold">
            {{ $error }}
        </div>
    @endif

    @if($isDetachedHead)
        <div class="bg-orange-950 border-b-2 border-orange-800 text-orange-200 px-4 py-3 space-y-2">
            <div class="text-xs uppercase tracking-wider font-bold">HEAD detached at {{ substr($currentBranch, 0, 7) }}</div>
            <flux:button 
                @click="$wire.showCreateModal = true"
                variant="primary" 
                size="sm"
                class="w-full uppercase tracking-wider text-xs"
            >
                Create branch here
            </flux:button>
        </div>
    @endif

    <div class="px-4 py-4 border-b-2 border-zinc-800 space-y-3">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">Current Branch</div>
            <flux:button 
                @click="$wire.showCreateModal = true"
                variant="ghost" 
                size="sm"
                icon="plus"
                class="text-xs uppercase tracking-wider"
            >
                New
            </flux:button>
        </div>

        <div class="flex items-center gap-2">
            <div class="text-lg font-bold text-zinc-100 truncate flex-1">
                {{ $isDetachedHead ? '(detached)' : $currentBranch }}
            </div>
            @if(!$isDetachedHead && ($aheadBehind['ahead'] > 0 || $aheadBehind['behind'] > 0))
                <div class="flex items-center gap-1.5">
                    @if($aheadBehind['ahead'] > 0)
                        <flux:badge variant="solid" color="green" class="font-mono text-xs">
                            ↑{{ $aheadBehind['ahead'] }}
                        </flux:badge>
                    @endif
                    @if($aheadBehind['behind'] > 0)
                        <flux:badge variant="solid" color="red" class="font-mono text-xs">
                            ↓{{ $aheadBehind['behind'] }}
                        </flux:badge>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        <div class="sticky top-0 bg-zinc-900 border-b border-zinc-800 px-4 py-3">
            <div class="text-xs uppercase tracking-widest font-bold text-zinc-400">All Branches</div>
        </div>

        <div class="divide-y divide-zinc-800">
            @php
                $localBranches = collect($branches)->filter(fn($b) => !$b['isRemote'] && !str_contains($b['name'], 'remotes/'));
                $remoteBranches = collect($branches)->filter(fn($b) => $b['isRemote'] || str_contains($b['name'], 'remotes/'));
            @endphp

            @foreach($localBranches as $branch)
                <div class="group px-4 py-2.5 hover:bg-zinc-900 transition-colors flex items-center justify-between gap-3">
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
                                size="sm"
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
            @endforeach

            @if($remoteBranches->isNotEmpty())
                <div class="bg-zinc-900 px-4 py-2 border-t-2 border-zinc-800">
                    <div class="text-xs uppercase tracking-widest font-bold text-zinc-500">Remote Branches</div>
                </div>

                @foreach($remoteBranches as $branch)
                    @php
                        $cleanName = str_replace('remotes/', '', $branch['name']);
                    @endphp
                    <div class="px-4 py-2.5 hover:bg-zinc-900 transition-colors">
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
    </div>

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
                    @foreach($localBranches as $branch)
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
