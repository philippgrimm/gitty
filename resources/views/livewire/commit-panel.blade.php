<div 
    x-data="{ showDropdown: false, commitFlash: false, charCount: 0 }" 
    x-effect="charCount = $wire.message?.length || 0"
    x-on:committed.window="commitFlash = true; setTimeout(() => commitFlash = false, 200);"
    x-on:prefill-updated.window="$nextTick(() => { const ta = $el.querySelector('textarea'); if (ta) { ta.setSelectionRange(ta.value.length, ta.value.length); ta.focus(); } });"
    x-on:focus-commit-message.window="$nextTick(() => { const ta = $el.querySelector('textarea'); if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); } });"
    class="flex flex-col bg-[var(--surface-0)] text-[var(--text-primary)] tracking-wide border-t border-[var(--border-default)] p-3 gap-2 panel-inset"
>
    @if($error)
        <div class="bg-[var(--color-red)]/10 border border-[#D91440]/30 text-[var(--color-red)] px-3 py-2 text-xs font-mono uppercase tracking-wider font-semibold">
            {{ $error }}
        </div>
    @endif

    <div class="relative">
        {{-- Templates dropdown (overlaid on textarea) --}}
        <div class="absolute top-1 right-1 z-10 flex gap-1">
            @if(count($storedHistory) > 0)
                <flux:dropdown position="bottom-end">
                    <flux:tooltip content="Recent messages">
                        <flux:button variant="ghost" size="xs" square class="text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] !h-5 !w-5">
                            <x-pixelarticons-clock class="w-3 h-3" />
                        </flux:button>
                    </flux:tooltip>
                    <flux:menu class="max-h-64 overflow-y-auto">
                        @foreach($storedHistory as $historyMessage)
                            <flux:menu.item wire:click="selectHistoryMessage('{{ addslashes($historyMessage) }}')">
                                <div class="font-mono text-xs truncate max-w-xs">
                                    {{ \Illuminate\Support\Str::limit($historyMessage, 60) }}
                                </div>
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif

            <flux:dropdown position="bottom-end">
                <flux:tooltip content="Commit templates">
                    <flux:button variant="ghost" size="xs" square class="text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] !h-5 !w-5">
                        <x-pixelarticons-bulletlist class="w-3 h-3" />
                    </flux:button>
                </flux:tooltip>
                <flux:menu>
                    @foreach($this->getTemplates() as $template)
                        <flux:menu.item wire:click="applyTemplate('{{ addslashes($template['prefix']) }}')">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $template['label'] }}</span>
                                <span class="text-xs text-[var(--text-tertiary)]">{{ $template['description'] }}</span>
                            </div>
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>

        <flux:textarea 
            wire:model.live.debounce.300ms="message" 
            x-on:input="charCount = $event.target.value.length;"
            x-on:keydown.arrow-up.prevent="$wire.cycleHistory('up');"
            x-on:keydown.arrow-down.prevent="$wire.cycleHistory('down');"
            placeholder="Commit message"
            rows="auto"
            resize="vertical"
            class="bg-[var(--surface-1)] border-[var(--border-default)] text-[var(--text-primary)] placeholder-[#686C7C] font-mono text-sm focus:outline-none focus:ring-0 retro-input phosphor-glow"
        />
        <div class="absolute bottom-2 right-2 text-[10px] text-[var(--text-tertiary)] font-mono pointer-events-none select-none" x-text="charCount"></div>
    </div>

    @if(count($storedHistory) > 0)
        <div class="text-xs text-[#686C7C]">↑↓ message history</div>
    @endif

    <div class="flex w-full commit-button-group">
        <flux:button
            wire:click="commit"
            wire:loading.attr="disabled"
            wire:target="commit,commitAndPush,toggleAmend"
            variant="primary"
            size="sm"
            :disabled="$stagedCount === 0 || empty(trim($message))"
            class="flex-1 font-semibold disabled:!bg-[var(--surface-3)] disabled:!text-[var(--text-tertiary)] disabled:!border-[var(--border-default)] disabled:!shadow-none btn-bevel !border-r-0 !rounded-r-none commit-ready"
            x-bind:class="{
                'animate-commit-flash': commitFlash
            }"
        >
            {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
        </flux:button>

        <flux:dropdown position="top">
            <flux:button
                variant="primary"
                size="sm"
                square
                :disabled="$stagedCount === 0 || empty(trim($message))"
                class="disabled:!bg-[var(--surface-3)] disabled:!text-[var(--text-tertiary)] disabled:!border-[var(--border-default)] disabled:!shadow-none btn-bevel !rounded-l-none !border-l !border-l-[rgba(255,255,255,0.3)]"
            >
                <x-pixelarticons-chevron-up class="w-4 h-4" />
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="commit">
                    <x-slot:icon>
                        <x-pixelarticons-check class="w-4 h-4" />
                    </x-slot:icon>
                    {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
                </flux:menu.item>
                <flux:menu.item wire:click="commitAndPush">
                    <x-slot:icon>
                        <x-pixelarticons-arrow-up class="w-4 h-4" />
                    </x-slot:icon>
                    Commit & Push (⌘⇧↵)
                </flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="toggleAmend">
                    @if($isAmend)
                        <x-slot:icon>
                            <x-pixelarticons-check class="w-4 h-4" />
                        </x-slot:icon>
                    @endif
                    Amend Previous Commit
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    <flux:modal wire:model="showUndoConfirmation" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Undo Last Commit?</flux:heading>
            <flux:subheading class="font-mono">
                Changes from the last commit will return to the staging area.
                @if($lastCommitPushed)
                    <div class="mt-2 text-[var(--color-red)] font-bold">
                        ⚠ This commit has been pushed to remote. Undoing will require a force push.
                    </div>
                @endif
            </flux:subheading>
        </div>
        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showUndoConfirmation', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="confirmUndoLastCommit">Undo</flux:button>
        </div>
    </flux:modal>
</div>
