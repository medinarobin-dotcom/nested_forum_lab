<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class Comment
{
    public function __construct(private PDO $pdo) {}

    /** All comments of one discussion with their author name (flat list). */
    public function forDiscussion(int $discussionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username AS author
               FROM comments c
               JOIN users u ON u.id = c.user_id
              WHERE c.discussion_id = :discussion_id
              ORDER BY c.created_at, c.id'
        );
        $stmt->execute(['discussion_id' => $discussionId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, discussion_id, parent_id FROM comments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Displayed depth of a comment (top-level = 0), found by walking up parent links.
     * Returns null if a cycle is detected or the walk exceeds $limit steps.
     */
    public function depthOf(int $commentId, int $limit = 100): ?int
    {
        $depth = 0;
        $seen = [];
        $current = $this->find($commentId);
        while ($current !== null && $current['parent_id'] !== null) {
            if (isset($seen[$current['id']]) || $depth >= $limit) {
                return null;
            }
            $seen[$current['id']] = true;
            $depth++;
            $current = $this->find((int) $current['parent_id']);
        }
        return $current === null ? null : $depth;
    }

    /** Insert a comment. $userId always comes from the session, never from the form. */
    public function create(int $discussionId, ?int $parentId, int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (discussion_id, parent_id, user_id, body)
             VALUES (:discussion_id, :parent_id, :user_id, :body)'
        );
        $stmt->bindValue('discussion_id', $discussionId, PDO::PARAM_INT);
        $stmt->bindValue('parent_id', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('body', $body, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }
}
