<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = [
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'account_name' => 'My Wallet',
                'account_number' => '1234567890',
                'bank_name' => 'Bank A',
                'amount' => 1000,
                'status' => 'pending'
            ]
        ];

        foreach($requests as $request) {
            WithdrawRequest::create($request);
        }
    }
}
