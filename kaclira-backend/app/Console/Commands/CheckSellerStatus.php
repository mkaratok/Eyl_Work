<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckSellerStatus extends Command
{
    protected $signature = 'seller:check-status {email=seller@kaclira.com}';
    protected $description = 'Check seller user status and activate if needed';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Checking seller with email: {$email}");
        
        // Find user with seller role
        $user = User::whereHas('roles', function($q) {
            $q->where('name', 'seller');
        })->where('email', $email)->first();
        
        if (!$user) {
            $this->error("Seller with email {$email} not found!");
            return 1;
        }
        
        $this->info("Found seller: {$user->name} (ID: {$user->id})");
        $this->info("Is active: " . ($user->is_active ? 'Yes' : 'No'));
        
        // If user is not active, activate them
        if (!$user->is_active) {
            $user->is_active = true;
            $user->save();
            $this->info("Seller has been activated.");
        }
        
        // Show roles
        $roles = $user->getRoleNames();
        $this->info("Roles: " . implode(', ', $roles->toArray()));
        
        return 0;
    }
}
