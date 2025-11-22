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
        
        $withdrawRequest->update([
            'status' => $payload['status'],
            'reason' => $payload['reason'] ?? null
        ]);

        return $this->respondWithCustomData([
            'message' => 'Request updated successfully'
        ]);
    }
}
