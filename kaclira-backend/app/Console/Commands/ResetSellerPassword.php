<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetSellerPassword extends Command
{
    protected $signature = 'seller:reset-password {email} {password=password123}';
    protected $description = 'Reset a seller user password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $this->info("Attempting to reset password for seller with email: {$email}");
        
        $user = User::whereHas('roles', function($q) {
            $q->where('name', 'seller');
        })->where('email', $email)->first();
        
        if (!$user) {
            $this->error("Seller with email {$email} not found!");
            return 1;
        }
        
        $user->password = Hash::make($password);
        $user->save();
        
        $this->info("Password for seller {$user->name} ({$email}) has been reset to: {$password}");
        return 0;
    }
}
