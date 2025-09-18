<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\SyncGoogleCategories;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The SyncGoogleCategories command is already registered via the $commands array in App\Console\Kernel
