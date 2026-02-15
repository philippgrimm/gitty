# Decisions — performance-optimization

## Highlight.js chosen over Shiki WASM
- Lightweight, fast, well-supported
- No WASM overhead for desktop app
- Sufficient language support for code diffs

## Polling optimized, not replaced with FS watchers
- FS watchers too complex for NativePHP
- Intervals increased: 3s→5s, 5s→15s, 10s→30s
- Hash-based skip-if-unchanged added

## Commit textarea → wire:model.blur (not debounce)
- Message only matters at commit time
- Character counter moved to Alpine.js for instant updates

## Optimistic UI deferred
- wire:loading + event cascade fix provides sufficient perceived speed
- Full optimistic UI adds rollback complexity not warranted yet
