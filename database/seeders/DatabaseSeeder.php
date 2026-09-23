<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'full_name' => 'Primary Administrator',
            ]
        );
        $admin->forceFill(['is_primary' => true, 'role' => 'admin'])->save();

        User::firstOrCreate(
            ['username' => 'admin1'],
            [
                'password' => Hash::make('admin1'),
                'role' => 'admin',
                'full_name' => 'Administrator',
                'is_primary' => false,
            ]
        );

        User::firstOrCreate(
            ['username' => 'user'],
            [
                'password' => Hash::make('user'),
                'role' => 'user',
                'full_name' => 'Regular User',
                'is_primary' => false,
            ]
        );

        $wallets = [
            [
                'name' => 'BCA',
                'initial_balance' => 50000000,
                'current_balance' => 50000000,
            ],
            [
                'name' => 'Mandiri',
                'initial_balance' => 25000000,
                'current_balance' => 25000000,
            ],
            [
                'name' => 'Cash',
                'initial_balance' => 10000000,
                'current_balance' => 10000000,
            ],
        ];

        foreach ($wallets as $walletData) {
            Wallet::firstOrCreate(
                ['name' => $walletData['name']],
                $walletData
            );
        }

        $categories = [
            [
                'name' => 'Salary',
                'type' => 'income',
                'amount' => 10000000,
            ],
            [
                'name' => 'Freelance',
                'type' => 'income',
                'amount' => null,
            ],
            [
                'name' => 'Food & Beverage',
                'type' => 'expense',
                'amount' => null,
            ],
            [
                'name' => 'Utilities',
                'type' => 'expense',
                'amount' => 1000000,
            ],
        ];

        foreach ($categories as $catData) {
            Category::firstOrCreate(
                ['name' => $catData['name'], 'type' => $catData['type']],
                $catData
            );
        }

        $tags = [
            [
                'name' => 'Essential',
                'color' => '#696CFF',
            ],
            [
                'name' => 'Urgent',
                'color' => '#FF3E1D',
            ],
            [
                'name' => 'Personal',
                'color' => '#71DD37',
            ],
        ];

        foreach ($tags as $tagData) {
            Tag::firstOrCreate(
                ['name' => $tagData['name']],
                $tagData
            );
        }
    }
}