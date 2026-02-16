<div 
    x-data="{ showDropdown: false, commitFlash: false, charCount: 0, historyIndex: -1, draft: '', browsingHistory: false }" 
    x-init="charCount = $wire.message?.length || 0"
    x-on:committed.window="commitFlash = true; setTimeout(() => commitFlash = false, 200); historyIndex = -1; browsingHistory = false;"
    x-on:prefill-updated.window="$nextTick(() => { const ta = $el.querySelector('textarea'); if (ta) { ta.setSelectionRange(ta.value.length, ta.value.length); ta.focus(); } }); historyIndex = -1; browsingHistory = false;"
    class="flex flex-col bg-[#eff1f5] text-[#4c4f69] font-mono border-t border-[#ccd0da] p-3 gap-2"
>
    @if($error)
        <div class="bg-[#d20f39]/10 border border-[#d20f39]/30 text-[#d20f39] px-3 py-2 text-xs font-mono uppercase tracking-wider font-semibold">
            {{ $error }}
        </div>
    @endif

    <div class="relative">
        <flux:textarea 
            wire:model.live.debounce.300ms="message" 
            x-on:input="charCount = $event.target.value.length; historyIndex = -1; browsingHistory = false;"
            x-on:keydown.arrow-up.prevent="
                const history = $wire.commitHistory || [];
                if (history.length === 0) return;
                if (!browsingHistory) {
                    draft = $event.target.value;
                    browsingHistory = true;
                    historyIndex = -1;
                }
                if (historyIndex < history.length - 1) {
                    historyIndex++;
                    $wire.set('message', history[historyIndex]);
                    charCount = history[historyIndex].length;
                    $nextTick(() => { $event.target.setSelectionRange($event.target.value.length, $event.target.value.length); });
                }
            "
            x-on:keydown.arrow-down.prevent="
                if (!browsingHistory) return;
                if (historyIndex > 0) {
                    historyIndex--;
                    const history = $wire.commitHistory || [];
                    $wire.set('message', history[historyIndex]);
                    charCount = history[historyIndex].length;
                    $nextTick(() => { $event.target.setSelectionRange($event.target.value.length, $event.target.value.length); });
                } else if (historyIndex === 0) {
                    historyIndex = -1;
                    browsingHistory = false;
                    $wire.set('message', draft);
                    charCount = draft.length;
                    $nextTick(() => { $event.target.setSelectionRange($event.target.value.length, $event.target.value.length); });
                }
            "
            x-on:keydown.escape="
                if (browsingHistory) {
                    historyIndex = -1;
                    browsingHistory = false;
                    $wire.set('message', draft);
                    charCount = draft.length;
                    $nextTick(() => { $event.target.setSelectionRange($event.target.value.length, $event.target.value.length); });
                }
            "
            placeholder="Commit message"
            rows="auto"
            resize="vertical"
            class="bg-[#e6e9ef] border-[#ccd0da] text-[#4c4f69] placeholder-[#9ca0b0] font-mono text-sm focus:outline-none focus:ring-2 focus:ring-[#084CCF]/30 focus:border-[#084CCF]"
        />
        <div class="absolute bottom-2 right-2 text-[10px] text-[#9ca0b0] font-mono pointer-events-none select-none" x-text="charCount"></div>
    </div>

    <flux:button.group class="w-full">
        <flux:button 
            wire:click="commit"
            wire:loading.attr="disabled"
            wire:target="commit,commitAndPush,toggleAmend"
            variant="primary"
            size="sm"
            :disabled="$stagedCount === 0 || empty(trim($message))"
            class="flex-1 font-semibold disabled:!bg-[#ccd0da] disabled:!text-[#8c8fa1] disabled:!border-[#ccd0da] disabled:!shadow-none"
            x-bind:class="{ 
                'animate-commit-flash': commitFlash
            }"
        >
            {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
        </flux:button>

        <flux:dropdown position="top">
            <flux:button 
                icon:trailing="chevron-up"
                variant="primary"
                size="sm"
                square
                :disabled="$stagedCount === 0 || empty(trim($message))"
                class="disabled:!bg-[#ccd0da] disabled:!text-[#8c8fa1] disabled:!border-[#ccd0da] disabled:!shadow-none"
            />
            <flux:menu>
                <flux:menu.item wire:click="commit" icon="check">
                    {{ $isAmend ? 'Amend' : 'Commit' }} (⌘↵)
                </flux:menu.item>
                <flux:menu.item wire:click="commitAndPush" icon="arrow-up-tray">
                    Commit & Push (⌘⇧↵)
                </flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="toggleAmend" :icon="$isAmend ? 'check' : ''">
                    Amend Previous Commit
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:button.group>
</div>
