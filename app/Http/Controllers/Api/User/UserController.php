<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class UserController extends  Controller
{
    public function index()
    {
        $requests = WithdrawRequest::query()
            ->when(request('status'), function ($query) {
                $query->where('status', request('status'));
            })
            ->with('user')
            ->paginate(3);

        return $this->respondWithCustomData([
            'requests' => $requests,
            'message' => 'Requests retrieved successfully'
        ]);
    }

    public function update(WithdrawRequest $withdrawRequest, Request $request)
    {
        $payload = $request->validate([
            'status' => 'required|in:pending,paid,cancelled,rejected',
            'reason' => 'required_if:status,cancelled,rejected'
        ]);
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($withdrawRequest, $payload) {
            $withdrawRequest->update([
                'status' => $payload['status'],
                'reason' => $payload['reason'] ?? null
            ]);

            $transaction = $withdrawRequest->transaction;

            if ($transaction) {
                if ($payload['status'] === 'paid') {
                    $transaction->update(['status' => 2]); // Successful
                } elseif (in_array($payload['status'], ['cancelled', 'rejected'])) {
                    // Refund the user if not already refunded (check transaction status to be safe)
                    if ($transaction->status !== 3) {
                        $transaction->update(['status' => 3]); // Failed
                        $withdrawRequest->user->wallet()->increment('balance', $transaction->amount);
                    }
                }
            }
        });

        return $this->respondWithCustomData([
            'message' => 'Request updated successfully'
        ]);
    }
}
