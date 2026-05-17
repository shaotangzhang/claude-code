<?php

declare(strict_types=1);

namespace Acme\Commerce\Services;

use Acme\Commerce\Events\ReviewSubmitted;
use Acme\Commerce\Models\Review;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

final class ReviewService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function submit(
        string $productId,
        ?string $userId,
        int $rating,
        ?string $title = null,
        ?string $body = null,
        ?string $orderId = null,
        bool $autoApprove = false,
    ): Review {
        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException("Rating must be 1..5; got {$rating}.");
        }

        $review = Review::create([
            'product_id' => $productId,
            'user_id'    => $userId,
            'order_id'   => $orderId,
            'rating'     => $rating,
            'title'      => $title,
            'body'       => $body,
            'status'     => $autoApprove ? Review::STATUS_APPROVED : Review::STATUS_PENDING,
        ]);

        $this->events->dispatch(new ReviewSubmitted($review->id, $productId, $userId, $rating, $review->status));

        return $review;
    }

    public function approve(Review $review): void
    {
        $review->status = Review::STATUS_APPROVED;
        $review->save();
    }

    public function markSpam(Review $review): void
    {
        $review->status = Review::STATUS_SPAM;
        $review->save();
    }
}
