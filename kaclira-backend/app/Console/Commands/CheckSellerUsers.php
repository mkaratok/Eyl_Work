<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CheckSellerUsers extends Command
{
    protected $signature = 'check:seller-users';
    protected $description = 'Check if there are any seller users in the database';

    public function handle()
    {
        $this->info('Checking for seller users...');
        
        // Check if seller role exists
        $sellerRole = Role::where('name', 'seller')->first();
        if (!$sellerRole) {
            $this->error('Seller role does not exist!');
            return 1;
        }
        
        $this->info('Seller role exists with ID: ' . $sellerRole->id);
        
        // Get users with seller role
        $sellerUsers = User::role('seller')->get();
        
        if ($sellerUsers->isEmpty()) {
            $this->warn('No users with seller role found!');
            
            // Create a test seller user
            $this->info('Creating a test seller user...');
            $user = User::create([
                'name' => 'Test Seller',
                'email' => 'seller@example.com',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]);
            
            $user->assignRole('seller');
            
            $this->info('Test seller user created with email: seller@example.com and password: password');
            return 0;
        }
        
        $this->info('Found ' . $sellerUsers->count() . ' seller users:');
        foreach ($sellerUsers as $user) {
            $this->line("- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Active: " . ($user->is_active ? 'Yes' : 'No'));
        }
        
        return 0;
    }
}
