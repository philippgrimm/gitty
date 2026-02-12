<div>
    <flux:modal wire:model="showModal" class="space-y-6">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Settings</flux:heading>
            <flux:subheading class="font-mono">
                Configure application settings
            </flux:subheading>
        </div>

        <div class="space-y-6">
            <div class="space-y-4">
                <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 border-b border-zinc-800 pb-2">
                    Git
                </div>
                
                <flux:field>
                    <flux:label class="font-mono">Auto-fetch interval (seconds, 0 = disabled)</flux:label>
                    <flux:input 
                        wire:model="autoFetchInterval"
                        type="number"
                        min="0"
                        class="font-mono"
                    />
                </flux:field>

                <flux:field>
                    <flux:label class="font-mono">Default branch</flux:label>
                    <flux:input 
                        wire:model="defaultBranch"
                        type="text"
                        class="font-mono"
                    />
                </flux:field>

                <flux:field>
                    <flux:label class="font-mono">Diff context lines</flux:label>
                    <flux:input 
                        wire:model="diffContextLines"
                        type="number"
                        min="0"
                        class="font-mono"
                    />
                </flux:field>
            </div>

            <div class="space-y-4">
                <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 border-b border-zinc-800 pb-2">
                    Editor
                </div>
                
                <flux:field>
                    <flux:label class="font-mono">External editor</flux:label>
                    <flux:input 
                        wire:model="externalEditor"
                        type="text"
                        placeholder="code, vim, nano, or custom path"
                        class="font-mono"
                    />
                </flux:field>
            </div>

            <div class="space-y-4">
                <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 border-b border-zinc-800 pb-2">
                    Appearance
                </div>
                
                <flux:field>
                    <flux:label class="font-mono">Theme</flux:label>
                    <flux:select wire:model="theme" class="font-mono">
                        <flux:select.option value="dark">Dark</flux:select.option>
                        <flux:select.option value="light">Light</flux:select.option>
                        <flux:select.option value="system">System</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="space-y-4">
                <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 border-b border-zinc-800 pb-2">
                    Confirmations
                </div>
                
                <flux:checkbox 
                    wire:model="confirmDiscard"
                    label="Confirm before discarding changes"
                />

                <flux:checkbox 
                    wire:model="confirmForcePush"
                    label="Confirm before force push"
                />
            </div>

            <div class="space-y-4">
                <div class="text-xs uppercase tracking-widest font-bold text-zinc-400 border-b border-zinc-800 pb-2">
                    Display
                </div>
                
                <flux:checkbox 
                    wire:model="showUntracked"
                    label="Show untracked files"
                />
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-zinc-800 pt-4">
            <flux:button 
                variant="ghost" 
                wire:click="resetToDefaults"
                class="text-xs uppercase tracking-wider text-orange-400 hover:text-orange-300"
            >
                Reset to Defaults
            </flux:button>

            <div class="flex gap-2">
                <flux:button variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <flux:button 
                    variant="primary" 
                    wire:click="save"
                    class="uppercase tracking-wider"
                >
                    Save
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
