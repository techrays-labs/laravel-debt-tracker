<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures;

use Illuminate\Support\Collection;

/**
 * Fixture: known N+1 patterns for deterministic detector tests.
 *
 *  - processJobs():   $job->user inside foreach          → 1 flag, base score 6
 *  - sendInvoices():  ->lineItems()->count() in ->each() → 1 flag, base score 10
 *  - renderPosts():   ->with('comments') precedes loop   → 0 flags (suppressed)
 *  - printIds():      $item->id / ->created_at only      → 0 flags (ignored props)
 */
class N1QueryFixture
{
    /** @param object[] $jobs */
    public function processJobs(array $jobs): void
    {
        foreach ($jobs as $job) {
            // N+1: lazy-load relationship on loop variable — should be flagged
            echo $job->user;
        }
    }

    public function sendInvoices(Collection $orders): void
    {
        $orders->each(function ($order) {
            // N+1: chained query call inside collection iterator — should be flagged
            echo $order->lineItems()->count();
        });
    }

    /** @param object[] $posts */
    public function renderPosts(array $posts): void
    {
        // Eager load present within 30 lines before foreach — suppresses flagging
        $posts = \App\Models\Post::with('comments')->get();
        foreach ($posts as $post) {
            echo $post->comments;
        }
    }

    /** @param object[] $items */
    public function printIds(array $items): void
    {
        foreach ($items as $item) {
            // Both properties are in the ignore list — not flagged
            echo $item->id;
            echo $item->created_at;
        }
    }
}
