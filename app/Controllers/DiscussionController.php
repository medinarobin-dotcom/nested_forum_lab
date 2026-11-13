<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Discussion;
use App\Support\CommentTree;
use App\Support\View;

final class DiscussionController extends Controller
{
    public function __construct(
        private Discussion $discussions,
        private Comment $comments,
        View $view,
        private array $config
    ) {
        parent::__construct($view);
    }

    /** GET / */
    public function index(): void
    {
        $this->view->render('home', ['discussions' => $this->discussions->all()]);
    }

    /** GET /discussions/{id} */
    public function show(string $rawId): void
    {
        // 1. Validate the ID. Malformed ID -> 400 (documented choice).
        $id = self::parseId($rawId);
        if ($id === null) {
            $this->error(400, 'Bad request: the discussion ID must be a positive whole number.');
            return;
        }

        // 2. Fetch the discussion. Valid but missing -> 404 (comment query is skipped).
        $discussion = $this->discussions->find($id);
        if ($discussion === null) {
            $this->error(404, 'Discussion not found.');
            return;
        }

        // 3. Fetch comments and group them by parent into a tree.
        $tree = CommentTree::build(
            $this->comments->forDiscussion($id),
            $this->config['indent_cap'] ?? 8,
            $this->config['max_render_nodes'] ?? 2000
        );

        // 4. Render.
        $this->view->render('discussion', [
            'title'      => $discussion['title'],
            'discussion' => $discussion + ['created_at_utc' => $discussion['created_at'] . ' UTC'],
            'comments'   => $tree,
            'no_comments' => $tree === [],
        ]);
    }
}
