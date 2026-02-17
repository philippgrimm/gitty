# Issues

## Code Bugs
- `AutoFetchIndicator.php` and `SyncPanel.php` call `app(NotificationService::class)` without importing `App\Services\NotificationService`. Since they're in `App\Livewire` namespace, PHP resolves to nonexistent `App\Livewire\NotificationService`.

## Missing Components  
- `StashPanel.php` was merged into `RepoSidebar.php`. Tests still reference the old standalone component.

## Stale Test Expectations
- CommandPaletteTest expects 24 commands, now has 29
- SettingsModalTest expects 8 settings, now has 9
- SettingsServiceTest expects 8 defaults, now has 9
- ThemeToggleTest expects 'system' default, actual is 'dark'
- DiffServiceTest calls removed `renderDiffHtml()` method
- DiffViewerTest binary file handling changed
- RepoSidebarTest tag expectations wrong
- StatusUpdatedEventTest ahead count mismatch
