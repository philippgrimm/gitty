# Gitty — Decisions

## Architectural Decisions
- NativePHP + Electron (Tauri not available in NativePHP v2)
- Shell out to git CLI (not PHP git library) — most reliable, like lazygit
- Server-rendered HTML diffs with Shiki highlighting
- Single window mode (no multi-window SQLite concurrency issues)
- Hunk-level staging for MVP (line-level deferred)
- Auto-refresh via polling .git/index
- SQLite for app data only (never for git data)
