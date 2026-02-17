<div>
    <flux:modal wire:model="showModal" class="max-w-lg">
        <div>
            <flux:heading size="lg" class="font-mono uppercase tracking-wider">Keyboard Shortcuts</flux:heading>
        </div>
        
        <div class="space-y-5 mt-4 font-mono">
            {{-- General --}}
            <div>
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)] mb-2">General</div>
                <div class="space-y-1.5">
                    @foreach([
                        ['⌘K', 'Command Palette'],
                        ['⌘⇧P', 'Command Palette'],
                        ['⌘B', 'Toggle Sidebar'],
                        ['⌘/', 'Keyboard Shortcuts'],
                        ['Esc', 'Close / Cancel'],
                    ] as [$keys, $label])
                        <div class="flex items-center justify-between py-0.5">
                            <span class="text-sm text-[var(--text-primary)]">{{ $label }}</span>
                            <kbd class="text-[10px] text-[var(--text-secondary)] bg-[var(--surface-0)] border border-[var(--border-default)] rounded px-1.5 py-0.5 font-mono">{{ $keys }}</kbd>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Staging --}}
            <div>
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)] mb-2">Staging</div>
                <div class="space-y-1.5">
                    @foreach([
                        ['⌘⇧K', 'Stage All'],
                        ['⌘⇧U', 'Unstage All'],
                        ['⌘⇧S', 'Stash'],
                        ['⌘A', 'Select All Files'],
                    ] as [$keys, $label])
                        <div class="flex items-center justify-between py-0.5">
                            <span class="text-sm text-[var(--text-primary)]">{{ $label }}</span>
                            <kbd class="text-[10px] text-[var(--text-secondary)] bg-[var(--surface-0)] border border-[var(--border-default)] rounded px-1.5 py-0.5 font-mono">{{ $keys }}</kbd>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Committing --}}
            <div>
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)] mb-2">Committing</div>
                <div class="space-y-1.5">
                    @foreach([
                        ['⌘↵', 'Commit'],
                        ['⌘⇧↵', 'Commit & Push'],
                        ['⌘Z', 'Undo Last Commit'],
                    ] as [$keys, $label])
                        <div class="flex items-center justify-between py-0.5">
                            <span class="text-sm text-[var(--text-primary)]">{{ $label }}</span>
                            <kbd class="text-[10px] text-[var(--text-secondary)] bg-[var(--surface-0)] border border-[var(--border-default)] rounded px-1.5 py-0.5 font-mono">{{ $keys }}</kbd>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation --}}
            <div>
                <div class="text-xs uppercase tracking-wider font-medium text-[var(--text-tertiary)] mb-2">Navigation</div>
                <div class="space-y-1.5">
                    @foreach([
                        ['⌘H', 'Toggle History'],
                        ['⌘F', 'Search'],
                    ] as [$keys, $label])
                        <div class="flex items-center justify-between py-0.5">
                            <span class="text-sm text-[var(--text-primary)]">{{ $label }}</span>
                            <kbd class="text-[10px] text-[var(--text-secondary)] bg-[var(--surface-0)] border border-[var(--border-default)] rounded px-1.5 py-0.5 font-mono">{{ $keys }}</kbd>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </flux:modal>
</div>
