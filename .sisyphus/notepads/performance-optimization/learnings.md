# Learnings — performance-optimization

## Conventions
- Commit style: `type(scope): lowercase message` (e.g., `perf(staging): ...`)
- Test command: `php artisan test --compact`
- Build command: `npm run build`
- Pint formatting: `vendor/bin/pint --dirty --format agent`

## Architecture
- 12 Livewire components, 11 Git service classes, 10 DTOs
- All git operations via Symfony Process (synchronous)
- GitCacheService with TTL-based caching + group invalidation
- 5 components with active polling (3s, 5s, 5s, 10s, 30s)
- `status-updated` event dispatched by 7 components, listened by 3

## Key Files
- StagingPanel: app/Livewire/StagingPanel.php + resources/views/livewire/staging-panel.blade.php
- DiffViewer: app/Livewire/DiffViewer.php + resources/views/livewire/diff-viewer.blade.php
- CommitPanel: app/Livewire/CommitPanel.php + resources/views/livewire/commit-panel.blade.php
- GitCacheService: app/Services/Git/GitCacheService.php
- DiffService: app/Services/Git/DiffService.php (Shiki highlighting here)
