<?php

declare(strict_types=1);

namespace App\Helpers;

class FileTreeBuilder
{
    public static function buildTree(array $flatFiles): array
    {
        $tree = [];
        
        foreach ($flatFiles as $file) {
            $path = $file['path'];
            $parts = explode('/', $path);
            $current = &$tree;
            
            for ($i = 0; $i < count($parts) - 1; $i++) {
                $dirName = $parts[$i];
                $dirPath = implode('/', array_slice($parts, 0, $i + 1)) . '/';
                
                $found = false;
                foreach ($current as &$node) {
                    if ($node['type'] === 'directory' && $node['name'] === $dirName) {
                        $current = &$node['children'];
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $newDir = [
                        'name' => $dirName,
                        'type' => 'directory',
                        'path' => $dirPath,
                        'children' => [],
                    ];
                    $current[] = $newDir;
                    $current = &$current[count($current) - 1]['children'];
                }
            }
            
            $current[] = [
                'name' => basename($path),
                'type' => 'file',
                'path' => $path,
                'indexStatus' => $file['indexStatus'],
                'worktreeStatus' => $file['worktreeStatus'],
                'oldPath' => $file['oldPath'] ?? null,
            ];
        }
        
        return self::sortTree($tree);
    }
    
    private static function sortTree(array $tree): array
    {
        usort($tree, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            
            return strcasecmp($a['name'], $b['name']);
        });
        
        foreach ($tree as &$node) {
            if ($node['type'] === 'directory' && !empty($node['children'])) {
                $node['children'] = self::sortTree($node['children']);
            }
        }
        
        return $tree;
    }
}
