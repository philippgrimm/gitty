<div>
    {{-- Rebase Modal --}}
    <flux:modal wire:model="showRebaseModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Interactive Rebase</flux:heading>
            <flux:subheading class="font-mono text-xs">Rebase onto {{ substr($ontoCommit, 0, 8) }}</flux:subheading>
        </div>

        @if($showForceWarning)
            <div class="p-3 bg-[rgba(223,142,29,0.1)] rounded border border-[var(--color-yellow)]">
                <p class="text-sm text-[var(--color-yellow)] font-medium">⚠ These commits are pushed. Rebasing will require a force push.</p>
            </div>
        @endif

        <div 
            x-data="{
                draggedIndex: null,
                dragOverIndex: null,
                
                startDrag(index) {
                    this.draggedIndex = index;
                },
                
                dragOver(index, event) {
                    event.preventDefault();
                    this.dragOverIndex = index;
                },
                
                drop(index) {
                    if (this.draggedIndex === null || this.draggedIndex === index) {
                        this.draggedIndex = null;
                        this.dragOverIndex = null;
                        return;
                    }
                    
                    const plan = @js($rebasePlan);
                    const draggedItem = plan[this.draggedIndex];
                    plan.splice(this.draggedIndex, 1);
                    plan.splice(index, 0, draggedItem);
                    
                    const newOrder = plan.map((_, i) => i);
                    $wire.reorderCommits(newOrder);
                    
                    this.draggedIndex = null;
                    this.dragOverIndex = null;
                },
                
                endDrag() {
                    this.draggedIndex = null;
                    this.dragOverIndex = null;
                }
            }"
            class="space-y-2 max-h-[400px] overflow-y-auto"
        >
            @foreach($rebasePlan as $index => $commit)
                <div 
                    draggable="true"
                    @dragstart="startDrag({{ $index }})"
                    @dragover="dragOver({{ $index }}, $event)"
                    @drop="drop({{ $index }})"
                    @dragend="endDrag()"
                    :class="dragOverIndex === {{ $index }} ? 'border-[#084CCF] bg-[rgba(8,76,207,0.05)]' : 'border-[var(--border-default)]'"
                    class="flex items-center gap-3 p-3 border rounded cursor-move transition-colors"
                >
                    <div class="text-[var(--text-tertiary)]">
                        <x-phosphor-dots-six-vertical class="w-4 h-4" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-[#084CCF] font-mono">{{ $commit['shortSha'] }}</span>
                            <span class="text-sm text-[var(--text-primary)] truncate">{{ $commit['message'] }}</span>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <select 
                            wire:change="updateAction({{ $index }}, $event.target.value)"
                            class="text-xs border border-[var(--border-default)] rounded px-2 py-1 bg-white dark:bg-[var(--surface-0)] text-[var(--text-primary)]"
                        >
                            <option value="pick" {{ $commit['action'] === 'pick' ? 'selected' : '' }}>Pick</option>
                            <option value="squash" {{ $commit['action'] === 'squash' ? 'selected' : '' }}>Squash</option>
                            <option value="drop" {{ $commit['action'] === 'drop' ? 'selected' : '' }}>Drop</option>
                        </select>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-xs text-[var(--text-tertiary)] space-y-1">
            <div><strong>Pick:</strong> Keep this commit</div>
            <div><strong>Squash:</strong> Merge this commit into the previous one</div>
            <div><strong>Drop:</strong> Remove this commit</div>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showRebaseModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="startRebase">Start Rebase</flux:button>
        </div>
    </flux:modal>

    {{-- Active Rebase Banner --}}
    @if($isRebasing)
        <div class="fixed bottom-4 right-4 z-50 bg-[var(--color-yellow)] text-white rounded-lg shadow-lg p-4 max-w-md">
            <div class="flex items-start gap-3">
                <x-phosphor-git-merge class="w-5 h-5 shrink-0 mt-0.5" />
                <div class="flex-1">
                    <div class="font-medium mb-1">Rebase in Progress</div>
                    <div class="text-sm opacity-90 mb-3">Resolve conflicts and continue, or abort the rebase.</div>
                    <div class="flex gap-2">
                        <flux:button 
                            wire:click="continueRebase" 
                            variant="ghost" 
                            size="sm"
                            class="bg-white/20 hover:bg-white/30 text-white"
                        >
                            Continue
                        </flux:button>
                        <flux:button 
                            wire:click="abortRebase" 
                            variant="ghost" 
                            size="sm"
                            class="bg-white/20 hover:bg-white/30 text-white"
                        >
                            Abort
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
