<?php

declare(strict_types=1);

namespace Acme\Blog\Http\Controllers;

use Acme\Blog\Events\CommentReceived;
use Acme\Blog\Models\Article;
use Acme\Blog\Models\Comment;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CommentController extends Controller
{
    public function store(
        Request $request,
        Dispatcher $events,
        UserResolver $users,
        string $articleSlug,
    ): RedirectResponse {
        if (! config('acme.blog.comments.enabled')) {
            return back()->with('error', 'Comments disabled.');
        }

        $request->validate([
            'body'         => 'required|string|max:5000',
            'parent_id'    => 'nullable|string',
            'author_name'  => 'nullable|string|max:120',
            'author_email' => 'nullable|email',
        ]);

        $article = Article::query()->where('slug', $articleSlug)->firstOrFail();

        $status = config('acme.blog.comments.require_approval')
            ? Comment::STATUS_PENDING
            : Comment::STATUS_APPROVED;

        $comment = Comment::create([
            'article_id'     => $article->id,
            'parent_id'      => $request->input('parent_id'),
            'author_user_id' => $users->currentUserId(),
            'author_name'    => $request->input('author_name'),
            'author_email'   => $request->input('author_email'),
            'body'           => (string) $request->input('body'),
            'status'         => $status,
            'ip'             => $request->ip(),
            'user_agent'     => substr((string) $request->userAgent(), 0, 1024),
        ]);

        $events->dispatch(new CommentReceived($comment->id, $article->id, $status));

        return back()->with('status', $status === Comment::STATUS_PENDING ? 'Comment submitted for review.' : 'Comment posted.');
    }
}
