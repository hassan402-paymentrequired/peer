<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Events\NotificationCreated;
use App\Notifications\TournamentCompletedNotification;
use App\Notifications\PeerCompletedNotification;
use App\Notifications\PrizeWonNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function createNotification(
        User $user,
        string $title,
        string $message,
        string $type,
        array $data = []
    ): Notification {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
            ]);

            Log::info("Notification created for user {$user->id}: {$title}");

            return $notification;
        } catch (\Exception $e) {
            Log::error("Failed to create notification for user {$user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create tournament completion notifications for all participants
     */
    public function notifyTournamentCompletion($tournament, $winners, $totalPrizePool): void
    {
        try {
            $participants = $tournament->users;

            foreach ($participants as $participant) {
                $user = $participant;
                $isWinner = $winners->contains('user_id', $user->id);
                $winner = $winners->firstWhere('user_id', $user->id);

                $title = $isWinner ?
                    "🏆 You won in {$tournament->name}!" :
                    "Tournament {$tournament->name} completed";

                $message = $isWinner ?
                    "Congratulations! You finished with {$winner->total_points} points and won ₦" . number_format($winner->prize_amount ?? 0, 2) :
                    "The tournament has ended. You scored {$participant->total_points} points. Better luck next time!";

                $data = [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'final_rank' => $participant->rank ?? null,
                    'total_points' => $participant->total_points,
                    'is_winner' => $isWinner,
                    'prize_amount' => $isWinner ? ($winner->prize_amount ?? 0) : 0,
                    'total_prize_pool' => $totalPrizePool,
                ];

                // Create in-app notification
                $this->createNotification(
                    $user,
                    $title,
                    $message,
                    'tournament_completed',
                    $data
                );

                // Send WebPush notification
                $user->notify(new TournamentCompletedNotification(
                    $tournament->name,
                    $isWinner,
                    $participant->total_points ?? 0,
                    $isWinner ? ($winner->prize_amount ?? 0) : 0
                ));
            }

            Log::info("Tournament completion notifications sent for tournament {$tournament->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send tournament completion notifications: " . $e->getMessage());
        }
    }

    /**
     * Create peer completion notifications for all participants
     */
    public function notifyPeerCompletion($peer, $winner, $totalPrizePool): void
    {
        try {
            $participants = $peer->users;

            foreach ($participants as $participant) {
                $user = $participant;
                $isWinner = $participant->user_id === $winner->user_id;

                $title = $isWinner ?
                    "🎯 You won the peer '{$peer->name}'!" :
                    "Peer '{$peer->name}' completed";

                $message = $isWinner ?
                    "Congratulations! You won with {$winner->total_points} points and earned ₦" . number_format($winner->prize_amount ?? 0, 2) :
                    "The peer competition has ended. You scored {$participant->total_points} points. The winner was {$winner->user->name}.";

                $data = [
                    'peer_id' => $peer->id,
                    'peer_name' => $peer->name,
                    'total_points' => $participant->total_points,
                    'is_winner' => $isWinner,
                    'winner_name' => $winner->user->name ?? 'Unknown',
                    'winner_points' => $winner->total_points,
                    'prize_amount' => $isWinner ? ($winner->prize_amount ?? 0) : 0,
                    'total_prize_pool' => $totalPrizePool,
                ];

                // Create in-app notification
                $this->createNotification(
                    $user,
                    $title,
                    $message,
                    'peer_completed',
                    $data
                );

                // Send WebPush notification
                $user->notify(new PeerCompletedNotification(
                    $peer->name,
                    $isWinner,
                    $participant->total_points ?? 0,
                    $winner->user->name ?? 'Unknown',
                    $isWinner ? ($winner->prize_amount ?? 0) : 0
                ));
            }

            Log::info("Peer completion notifications sent for peer {$peer->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send peer completion notifications: " . $e->getMessage());
        }
    }

    /**
     * Create prize won notification
     */
    public function notifyPrizeWon(
        User $user,
        float $amount,
        string $competitionType,
        string $competitionName,
        array $additionalData = []
    ): void {
        try {
            $title = "🎉 Prize Won!";
            $message = "Congratulations! You won ₦" . number_format($amount, 2) . " in {$competitionName}!";

            $data = array_merge([
                'amount' => $amount,
                'competition_type' => $competitionType,
                'competition_name' => $competitionName,
                'new_balance' => $user->fresh()->balance ?? 0,
            ], $additionalData);

            // Create in-app notification
            $this->createNotification(
                $user,
                $title,
                $message,
                'prize_won',
                $data
            );

            // Send WebPush notification
            $user->notify(new PrizeWonNotification(
                $amount,
                $competitionType,
                $competitionName,
                $user->fresh()->balance ?? 0
            ));

            Log::info("Prize won notification sent to user {$user->id}: ₦{$amount}");
        } catch (\Exception $e) {
            Log::error("Failed to send prize won notification: " . $e->getMessage());
        }
    }

    /**
     * Create bulk notifications for multiple users
     */
    public function createBulkNotifications(
        array $userIds,
        string $title,
        string $message,
        string $type,
        array $data = []
    ): int {
        try {
            $notifications = [];
            $now = now();

            foreach ($userIds as $userId) {
                $notifications[] = [
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'data' => json_encode($data),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $insertedCount = Notification::insert($notifications);

            Log::info("Bulk notifications created: {$insertedCount} notifications");

            return $insertedCount;
        } catch (\Exception $e) {
            Log::error("Failed to create bulk notifications: " . $e->getMessage());
            throw $e;
        }
    }
}
