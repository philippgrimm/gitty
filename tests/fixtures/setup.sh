#!/bin/bash

set -e

if [ -z "$1" ]; then
    echo "Usage: bash tests/fixtures/setup.sh /path/to/base/directory"
    exit 1
fi

BASE_DIR="$1"

rm -rf "$BASE_DIR"
mkdir -p "$BASE_DIR"

echo "Creating test fixture repositories in: $BASE_DIR"

setup_git_config() {
    local repo_path="$1"
    git -C "$repo_path" config user.email "test@example.com"
    git -C "$repo_path" config user.name "Test User"
}

echo "1. Creating clean repo..."
CLEAN_REPO="$BASE_DIR/clean-repo"
mkdir -p "$CLEAN_REPO"
git -C "$CLEAN_REPO" init
setup_git_config "$CLEAN_REPO"
echo "# Clean Repository" > "$CLEAN_REPO/README.md"
git -C "$CLEAN_REPO" add .
git -C "$CLEAN_REPO" commit -m "Initial commit"

echo "2. Creating repo with unstaged changes..."
UNSTAGED_REPO="$BASE_DIR/unstaged-changes"
cp -r "$CLEAN_REPO" "$UNSTAGED_REPO"
echo "Modified content" >> "$UNSTAGED_REPO/README.md"
echo "New file" > "$UNSTAGED_REPO/new-file.txt"

echo "3. Creating repo with staged changes..."
STAGED_REPO="$BASE_DIR/staged-changes"
cp -r "$CLEAN_REPO" "$STAGED_REPO"
echo "Staged content" >> "$STAGED_REPO/README.md"
git -C "$STAGED_REPO" add README.md

echo "4. Creating repo with mixed staged/unstaged changes..."
MIXED_REPO="$BASE_DIR/mixed-changes"
cp -r "$CLEAN_REPO" "$MIXED_REPO"
echo "Staged line" >> "$MIXED_REPO/README.md"
git -C "$MIXED_REPO" add README.md
echo "Unstaged line" >> "$MIXED_REPO/README.md"
echo "New untracked file" > "$MIXED_REPO/untracked.txt"

echo "5. Creating repo with merge conflict..."
CONFLICT_REPO="$BASE_DIR/merge-conflict"
cp -r "$CLEAN_REPO" "$CONFLICT_REPO"
git -C "$CONFLICT_REPO" checkout -b feature
echo "Feature content" > "$CONFLICT_REPO/conflict.txt"
git -C "$CONFLICT_REPO" add conflict.txt
git -C "$CONFLICT_REPO" commit -m "Add feature content"
git -C "$CONFLICT_REPO" checkout master 2>/dev/null || git -C "$CONFLICT_REPO" checkout main
echo "Main content" > "$CONFLICT_REPO/conflict.txt"
git -C "$CONFLICT_REPO" add conflict.txt
git -C "$CONFLICT_REPO" commit -m "Add main content"
git -C "$CONFLICT_REPO" merge feature || true

echo "6. Creating repo with detached HEAD..."
DETACHED_REPO="$BASE_DIR/detached-head"
cp -r "$CLEAN_REPO" "$DETACHED_REPO"
COMMIT_SHA=$(git -C "$DETACHED_REPO" rev-parse HEAD)
git -C "$DETACHED_REPO" checkout "$COMMIT_SHA"

echo "7. Creating repo with stashes..."
STASH_REPO="$BASE_DIR/stashed-changes"
cp -r "$CLEAN_REPO" "$STASH_REPO"
echo "Stashed content 1" >> "$STASH_REPO/README.md"
git -C "$STASH_REPO" stash push -m "First stash"
echo "Stashed content 2" >> "$STASH_REPO/README.md"
git -C "$STASH_REPO" stash push -m "Second stash"

echo "8. Creating repo with multiple branches..."
BRANCHES_REPO="$BASE_DIR/multiple-branches"
cp -r "$CLEAN_REPO" "$BRANCHES_REPO"
git -C "$BRANCHES_REPO" checkout -b feature-a
echo "Feature A" > "$BRANCHES_REPO/feature-a.txt"
git -C "$BRANCHES_REPO" add feature-a.txt
git -C "$BRANCHES_REPO" commit -m "Add feature A"
git -C "$BRANCHES_REPO" checkout master 2>/dev/null || git -C "$BRANCHES_REPO" checkout main
git -C "$BRANCHES_REPO" checkout -b feature-b
echo "Feature B" > "$BRANCHES_REPO/feature-b.txt"
git -C "$BRANCHES_REPO" add feature-b.txt
git -C "$BRANCHES_REPO" commit -m "Add feature B"
git -C "$BRANCHES_REPO" checkout master 2>/dev/null || git -C "$BRANCHES_REPO" checkout main
git -C "$BRANCHES_REPO" checkout -b bugfix
echo "Bugfix" > "$BRANCHES_REPO/bugfix.txt"
git -C "$BRANCHES_REPO" add bugfix.txt
git -C "$BRANCHES_REPO" commit -m "Add bugfix"
git -C "$BRANCHES_REPO" checkout master 2>/dev/null || git -C "$BRANCHES_REPO" checkout main

echo "9. Creating repo with untracked files..."
UNTRACKED_REPO="$BASE_DIR/untracked-files"
cp -r "$CLEAN_REPO" "$UNTRACKED_REPO"
echo "Untracked 1" > "$UNTRACKED_REPO/untracked1.txt"
echo "Untracked 2" > "$UNTRACKED_REPO/untracked2.txt"
mkdir -p "$UNTRACKED_REPO/untracked-dir"
echo "Untracked in dir" > "$UNTRACKED_REPO/untracked-dir/file.txt"

echo "10. Creating repo with deleted files..."
DELETED_REPO="$BASE_DIR/deleted-files"
cp -r "$CLEAN_REPO" "$DELETED_REPO"
echo "File to delete" > "$DELETED_REPO/to-delete.txt"
git -C "$DELETED_REPO" add to-delete.txt
git -C "$DELETED_REPO" commit -m "Add file to delete"
rm "$DELETED_REPO/to-delete.txt"

echo "11. Creating repo with renamed files..."
RENAMED_REPO="$BASE_DIR/renamed-files"
cp -r "$CLEAN_REPO" "$RENAMED_REPO"
echo "Original content" > "$RENAMED_REPO/original.txt"
git -C "$RENAMED_REPO" add original.txt
git -C "$RENAMED_REPO" commit -m "Add original file"
git -C "$RENAMED_REPO" mv original.txt renamed.txt

echo "12. Creating repo with binary files..."
BINARY_REPO="$BASE_DIR/binary-files"
cp -r "$CLEAN_REPO" "$BINARY_REPO"
dd if=/dev/urandom of="$BINARY_REPO/binary.dat" bs=1024 count=1 2>/dev/null
git -C "$BINARY_REPO" add binary.dat
git -C "$BINARY_REPO" commit -m "Add binary file"
dd if=/dev/urandom of="$BINARY_REPO/binary.dat" bs=1024 count=1 2>/dev/null

echo ""
echo "All fixture repositories created successfully in: $BASE_DIR"
echo ""
echo "Repositories created:"
echo "  1. clean-repo          - Clean repository"
echo "  2. unstaged-changes    - Unstaged modifications"
echo "  3. staged-changes      - Staged modifications"
echo "  4. mixed-changes       - Mixed staged/unstaged"
echo "  5. merge-conflict      - Active merge conflict"
echo "  6. detached-head       - Detached HEAD state"
echo "  7. stashed-changes     - Stashed changes"
echo "  8. multiple-branches   - Multiple branches"
echo "  9. untracked-files     - Untracked files"
echo " 10. deleted-files       - Deleted files"
echo " 11. renamed-files       - Renamed files"
echo " 12. binary-files        - Modified binary files"
