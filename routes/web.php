<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Web Routes
|---------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('web')->group(function () {
    // Root
    Route::get('/', fn () => redirect()->intended(auth()->check() ? '/dashboard' : '/login'));

    // Auth (guest only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn() => view('auth.login'))->name('login');
        Route::post('/login', [AuthController::class, 'authenticate'])->middleware('throttle:5,1')->name('login.authenticate');
    });

    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Everything below requires an authenticated user
    Route::middleware('auth')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Tasks
        Route::resource('tasks', TaskController::class)->except(['show', 'destroy']);
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('tasks/{task}/start', [TaskController::class, 'start'])->name('tasks.start');
        Route::post('tasks/{task}/submit-for-check', [TaskController::class, 'submitForCheck'])->name('tasks.submit-for-check');
        Route::post('tasks/{task}/approve', [TaskController::class, 'approve'])->name('tasks.approve');
        Route::post('tasks/{task}/request-revision', [TaskController::class, 'requestRevision'])->name('tasks.request-revision');
        Route::post('tasks/{task}/reopen', [TaskController::class, 'reopen'])->name('tasks.reopen');

        // Shared status endpoint (PRD §17) — Kanban drag & drop + Change Status fallback
        Route::patch('tasks/{task}/status', [TaskController::class, 'changeStatus'])->name('tasks.status');
        // Kanban column "Load more" (DragDrop PRD §8.6)
        Route::get('tasks/board/column/{status}', [TaskController::class, 'boardColumn'])->name('tasks.board.column');

        // Comments
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
        Route::delete('comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

        // Attachments
        Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::get('attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
        Route::patch('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        // Admin — user management & roles
        Route::middleware('can:viewAny,'.App\Models\User::class)->prefix('admin')->name('admin.')->group(function () {
            Route::get('users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [\App\Http\Controllers\UserController::class, 'create'])->name('users.create');
            Route::post('users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
            Route::patch('users/{user}/status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.status');
            Route::patch('users/{user}/password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.password');
            Route::get('roles', [\App\Http\Controllers\UserController::class, 'roles'])->name('roles.index');
        });

        // Profile
        Route::get('/profile', function (Request $request) {
            return view('profile', ['profile' => $request->user()]);
        })->name('profile');
    });
});

require __DIR__.'/auth.php';