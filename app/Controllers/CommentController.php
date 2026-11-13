<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Discussion;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\View;

final class CommentController extends Controller
{
    public function __construct(
        private Discussion $discussions,
        private Comment $comments,
        View $view,
        private array $config
    ) {
        parent::__construct($view);
    }

    /** POST /discussions/{id}/comments */
    public function storeTopLevel(string $rawDiscussionId): void
    {
        $this->store($rawDiscussionId, null);
    }

    /** POST /discussions/{id}/comments/{parentId}/replies */
    public function storeReply(string $rawDiscussionId, string $rawParentId): void
    {
        $this->store($rawDiscussionId, $rawParentId);
    }

    private function store(string $rawDiscussionId, ?string $rawParentId): void
    {
        // 1. Must be logged in (checked on the server for every write).
        if (!Auth::check()) {
            View::flash('Please log in to post a reply.');
            View::redirect('/login');
            return;
        }

        // 2. CSRF token must match the one in the session.
        if (!Csrf::isValid($_POST['csrf_token'] ?? null)) {
            $this->error(403, 'Invalid or expired form token. Go back, reload the page and try again.');
            return;
        }

        // 3. Validate IDs and that the discussion exists.
        $discussionId = self::parseId($rawDiscussionId);
        if ($discussionId === null) {
            $this->error(400, 'Bad request: the discussion ID must be a positive whole number.');
            return;
        }
        if ($this->discussions->find($discussionId) === null) {
            $this->error(404, 'Discussion not found.');
            return;
        }

        // 4. If replying, the parent must exist, be in the SAME discussion, and not be too deep.
        $parentId = null;
        if ($rawParentId !== null) {
            $parentId = self::parseId($rawParentId);
            $parent = $parentId === null ? null : $this->comments->find($parentId);
            if ($parent === null || (int) $parent['discussion_id'] !== $discussionId) {
                $this->error(400, 'Bad request: the parent comment does not belong to this discussion.');
                return;
            }
            $maxDepth = $this->config['max_reply_depth'] ?? 20;
            $parentDepth = $this->comments->depthOf($parentId, $maxDepth + 1);
            if ($parentDepth === null || $parentDepth + 1 > $maxDepth) {
                $this->error(400, "Bad request: replies cannot go deeper than depth $maxDepth.");
                return;
            }
        }

        // 5. Validate the content.
        $body = trim((string) ($_POST['body'] ?? ''));
        $maxLength = $this->config['max_comment_length'] ?? 2000;
        if ($body === '' || mb_strlen($body) > $maxLength) {
            View::flash("Comment must be between 1 and $maxLength characters.");
            View::redirect("/discussions/$discussionId");
            return;
        }

        // 6. Save using the session user ID, then redirect (Post/Redirect/Get).
        $newId = $this->comments->create($discussionId, $parentId, Auth::id(), $body);
        View::flash('Your comment was posted.');
        View::redirect("/discussions/$discussionId#comment-$newId");
    }
}
