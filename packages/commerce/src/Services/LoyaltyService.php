<?php

declare(strict_types=1);

namespace Acme\Commerce\Services;

use Acme\Commerce\Events\PointsAwarded;
use Acme\Commerce\Models\LoyaltyAccount;
use Acme\Commerce\Models\LoyaltyTransaction;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LoyaltyService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function accountFor(string $userId): LoyaltyAccount
    {
        return LoyaltyAccount::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'lifetime_earned' => 0]);
    }

    public function award(string $userId, int $points, string $refType, string $refId, ?string $reason = null): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw new RuntimeException("Award must be positive; got {$points}");
        }

        return DB::transaction(function () use ($userId, $points, $refType, $refId, $reason): LoyaltyTransaction {
            $a = $this->accountFor($userId);
            $a->balance         += $points;
            $a->lifetime_earned += $points;
            $a->save();

            $tx = LoyaltyTransaction::create([
                'account_id'     => $a->id,
                'type'           => LoyaltyTransaction::TYPE_EARN,
                'amount'         => $points,
                'balance_after'  => $a->balance,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'reason'         => $reason,
            ]);

            $this->events->dispatch(new PointsAwarded(
                userId:        $userId,
                points:        $points,
                referenceType: $refType,
                referenceId:   $refId,
                newBalance:    $a->balance,
            ));

            return $tx;
        });
    }

    public function redeem(string $userId, int $points, string $refType, string $refId): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw new RuntimeException("Redeem must be positive; got {$points}");
        }

        return DB::transaction(function () use ($userId, $points, $refType, $refId): LoyaltyTransaction {
            $a = $this->accountFor($userId);
            if ($a->balance < $points) {
                throw new RuntimeException("Insufficient points: balance={$a->balance}, requested={$points}");
            }
            $a->balance -= $points;
            $a->save();

            return LoyaltyTransaction::create([
                'account_id'     => $a->id,
                'type'           => LoyaltyTransaction::TYPE_REDEEM,
                'amount'         => -$points,
                'balance_after'  => $a->balance,
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ]);
        });
    }
}
