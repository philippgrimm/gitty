<?php

declare(strict_types=1);

namespace Tests\Mocks;

class GitOutputFixtures
{
    public static function statusClean(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +0 -0

OUTPUT;
    }

    public static function statusWithUnstagedChanges(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +0 -0
1 .M N... 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 README.md
1 .M N... 100644 100644 100644 c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2 c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2 src/index.php

OUTPUT;
    }

    public static function statusWithStagedChanges(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +1 -0
1 M. N... 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3 README.md
1 A. N... 000000 100644 100644 0000000000000000000000000000000000000000 e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4 new-file.txt

OUTPUT;
    }

    public static function statusWithMixedChanges(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head feature/mixed
# branch.upstream origin/feature/mixed
# branch.ab +2 -1
1 MM N... 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5 README.md
1 M. N... 100644 100644 100644 c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2 a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6 src/App.php
1 .M N... 100644 100644 100644 d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3 d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3 config/app.php
? untracked.txt

OUTPUT;
    }

    public static function statusWithUntrackedFiles(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +0 -0
? new-file.txt
? temp/cache.log
? .env.local

OUTPUT;
    }

    public static function statusWithDeletedFiles(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +0 -0
1 .D N... 100644 000000 000000 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 0000000000000000000000000000000000000000 deleted-file.txt
1 D. N... 100644 000000 000000 c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2 0000000000000000000000000000000000000000 staged-delete.txt

OUTPUT;
    }

    public static function statusWithRenamedFiles(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +1 -0
2 R. N... 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 R100 old-name.txt	new-name.txt

OUTPUT;
    }

    public static function statusWithConflict(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head main
# branch.upstream origin/main
# branch.ab +0 -0
u UU N... 100644 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2 d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3 conflict.txt

OUTPUT;
    }

    public static function statusDetachedHead(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head (detached)

OUTPUT;
    }

    public static function statusAheadBehind(): string
    {
        return <<<'OUTPUT'
# branch.oid a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
# branch.head feature/updates
# branch.upstream origin/feature/updates
# branch.ab +3 -2

OUTPUT;
    }

    public static function logOneline(): string
    {
        return <<<'OUTPUT'
a1b2c3d feat: add new feature
b2c3d4e fix: resolve bug in parser
c3d4e5f docs: update README
d4e5f6a refactor: improve performance
e5f6a7b test: add unit tests
f6a7b8c chore: update dependencies

OUTPUT;
    }

    public static function logWithDetails(): string
    {
        return <<<'OUTPUT'
commit a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
Author: John Doe <john@example.com>
Date:   Thu Feb 12 14:30:22 2026 +0100

    feat: add new feature
    
    This commit adds a new feature that improves the user experience.

commit b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1
Author: Jane Smith <jane@example.com>
Date:   Wed Feb 11 10:15:45 2026 +0100

    fix: resolve bug in parser
    
    Fixed an issue where the parser would fail on edge cases.

commit c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2
Author: John Doe <john@example.com>
Date:   Tue Feb 10 16:20:33 2026 +0100

    docs: update README
    
    Updated documentation with new examples.

OUTPUT;
    }

    public static function diffUnstaged(): string
    {
        return <<<'OUTPUT'
diff --git a/README.md b/README.md
index b2c3d4e..f6a7b8c 100644
--- a/README.md
+++ b/README.md
@@ -1,5 +1,7 @@
 # Project Title
 
-This is a sample project.
+This is a sample project with updated content.
+
+## New Section
 
 ## Installation

OUTPUT;
    }

    public static function diffStaged(): string
    {
        return <<<'OUTPUT'
diff --git a/src/App.php b/src/App.php
index c3d4e5f..a7b8c9d 100644
--- a/src/App.php
+++ b/src/App.php
@@ -10,7 +10,7 @@ class App
     
     public function run(): void
     {
-        echo "Hello World";
+        echo "Hello, Gitty!";
     }
 }

OUTPUT;
    }

    public static function branchList(): string
    {
        return <<<'OUTPUT'
* main
  feature/new-ui
  feature/api-improvement
  bugfix/parser-issue
  remotes/origin/HEAD -> origin/main
  remotes/origin/main
  remotes/origin/feature/new-ui
  remotes/origin/develop

OUTPUT;
    }

    public static function branchListVerbose(): string
    {
        return <<<'OUTPUT'
* main                   a1b2c3d feat: add new feature
  feature/new-ui         b2c3d4e feat: redesign UI
  feature/api-improvement c3d4e5f feat: improve API
  bugfix/parser-issue    d4e5f6a fix: resolve parser bug
  remotes/origin/HEAD    -> origin/main
  remotes/origin/main    a1b2c3d feat: add new feature
  remotes/origin/develop e5f6a7b chore: merge changes

OUTPUT;
    }

    public static function stashList(): string
    {
        return <<<'OUTPUT'
stash@{0}: WIP on main: a1b2c3d feat: add new feature
stash@{1}: On feature/new-ui: Temporary changes for testing
stash@{2}: WIP on bugfix/parser-issue: d4e5f6a fix: resolve parser bug

OUTPUT;
    }

    public static function remoteList(): string
    {
        return <<<'OUTPUT'
origin	git@github.com:user/project.git (fetch)
origin	git@github.com:user/project.git (push)
upstream	git@github.com:upstream/project.git (fetch)
upstream	git@github.com:upstream/project.git (push)

OUTPUT;
    }

    public static function remoteListVerbose(): string
    {
        return <<<'OUTPUT'
origin	git@github.com:user/project.git (fetch)
origin	git@github.com:user/project.git (push)
  HEAD branch: main
  Remote branches:
    main                   tracked
    feature/new-ui         tracked
    develop                tracked
  Local branches configured for 'git pull':
    main           merges with remote main
    feature/new-ui merges with remote feature/new-ui
  Local refs configured for 'git push':
    main           pushes to main           (up to date)
    feature/new-ui pushes to feature/new-ui (fast-forwardable)

OUTPUT;
    }

    public static function tagList(): string
    {
        return <<<'OUTPUT'
v1.0.0
v1.1.0
v1.2.0
v2.0.0-beta.1
v2.0.0

OUTPUT;
    }

    public static function showCommit(): string
    {
        return <<<'OUTPUT'
commit a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0
Author: John Doe <john@example.com>
Date:   Thu Feb 12 14:30:22 2026 +0100

    feat: add new feature
    
    This commit adds a new feature that improves the user experience.

diff --git a/src/Feature.php b/src/Feature.php
new file mode 100644
index 0000000..f6a7b8c
--- /dev/null
+++ b/src/Feature.php
@@ -0,0 +1,15 @@
+<?php
+
+namespace App;
+
+class Feature
+{
+    public function execute(): void
+    {
+        // Implementation
+    }
+}

OUTPUT;
    }
}
