<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Turns flat comment rows (each with a parent_id) into a nested tree for the view.
 * Depth is calculated here, not stored: top-level comments have displayed depth 0.
 */
final class CommentTree
{
    /**
     * @param array<int, array> $rows rows from Comment::forDiscussion()
     * @return array<int, array> top-level nodes, each with a "children" list
     */
    public static function build(array $rows, int $indentCap = 8, int $maxNodes = 2000): array
    {
        // 1. Group comments by parent: null parent => key "root".
        $byParent = [];
        foreach ($rows as $row) {
            $key = $row['parent_id'] === null ? 'root' : (int) $row['parent_id'];
            $byParent[$key][] = $row;
        }

        // 2. Walk from the roots. $visited + $maxNodes guard against cycles and huge threads.
        $visited = [];
        $count = 0;
        return self::children('root', 0, $byParent, $visited, $count, $indentCap, $maxNodes);
    }

    private static function children(
        int|string $parentKey,
        int $depth,
        array $byParent,
        array &$visited,
        int &$count,
        int $indentCap,
        int $maxNodes
    ): array {
        $nodes = [];
        foreach ($byParent[$parentKey] ?? [] as $row) {
            $id = (int) $row['id'];
            if (isset($visited[$id]) || $count >= $maxNodes) {
                continue; // already shown (cycle) or traversal limit reached
            }
            $visited[$id] = true;
            $count++;

            $kids = self::children($id, $depth + 1, $byParent, $visited, $count, $indentCap, $maxNodes);

            // Every key is always present so Mustache never "falls back" to a parent's value.
            $nodes[] = [
                'id'             => $id,
                'author'         => $row['author'],
                'body'           => $row['body'],
                'created_at'     => $row['created_at'] . ' UTC',
                'depth'          => $depth,
                'avatar'         => '/img/default-avatar.svg',
                'has_children'   => $kids !== [],
                'children'       => $kids,
                // Only indent while the child depth is within the cap.
                'indent_children' => ($depth + 1) <= $indentCap,
            ];
        }
        return $nodes;
    }
}
