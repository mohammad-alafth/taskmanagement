<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TaskStatusService;

class TaskServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TaskStatusService::class);
    }

    public function boot()
    {
        //
    }
}