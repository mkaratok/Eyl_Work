<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateTestSeller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-seller {--email=seller@test.com} {--password=12345678} {--name=Test Seller}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test seller user for login testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->warn("User with email {$email} already exists!");
            
            // Update password and ensure seller role
            $existingUser->password = Hash::make($password);
            $existingUser->is_active = true;
            $existingUser->save();
            
            // Ensure seller role exists
            $sellerRole = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
            
            // Assign seller role if not already assigned
            if (!$existingUser->hasRole('seller')) {
                $existingUser->assignRole('seller');
                $this->info("Assigned seller role to existing user.");
            }
            
            $this->info("Updated existing user password and ensured seller role.");
            $this->info("Email: {$email}");
            $this->info("Password: {$password}");
            return Command::SUCCESS;
        }

        try {
            // Create seller role if it doesn't exist
            $sellerRole = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
            $this->info("Seller role ensured.");

            // Create the test user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => '+1234567890',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign seller role
            $user->assignRole('seller');

            $this->info("Test seller user created successfully!");
            $this->info("Email: {$email}");
            $this->info("Password: {$password}");
            $this->info("Name: {$name}");
            $this->info("Role: seller");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create test seller: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}