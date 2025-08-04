<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserContrller;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\GroupMemberController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('users.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('users', UserContrller::class);

    Route::get('/chat/{user}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat-store', [ChatController::class, 'store'])->name('chat.store');
    Route::post('/chat/{user}/mark-as-read', [ChatController::class, 'markAsRead'])->name('chat.markAsRead');
    Route::post('/chat/{user}/mark-delivered', [ChatController::class, 'markAsDelivered'])->name('chat.markAsDelivered');

    // Group routes
    Route::resource('groups', GroupController::class);
    Route::post('groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::delete('groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    
    // Group messages
    Route::post('groups/{group}/messages', [GroupMessageController::class, 'store'])->name('group-messages.store');
    Route::delete('group-messages/{message}', [GroupMessageController::class, 'destroy'])->name('group-messages.destroy');
    
    // Group members management
    Route::post('groups/{group}/members', [GroupMemberController::class, 'store'])->name('group-members.store');
    Route::patch('groups/{group}/members/{user}', [GroupMemberController::class, 'update'])->name('group-members.update');
    Route::delete('groups/{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('group-members.destroy');

});

require __DIR__.'/auth.php';
