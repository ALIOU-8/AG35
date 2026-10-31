<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DuesController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// Site public
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/actualites', [SiteController::class, 'news'])->name('news.index');
Route::get('/actualites/{post}', [SiteController::class, 'article'])->name('news.show');
Route::post('/contact', [SiteController::class, 'contact'])->middleware('throttle:5,1')->name('contact.store');
Route::view('/hors-ligne', 'offline')->name('offline');

Route::middleware('auth')->group(function () {
    Route::get('/compte-en-attente', function () {
        return auth()->user()->isActive() ? redirect()->route('feed') : view('account.pending');
    })->name('account.pending');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Espace membres (application)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('feed'))->name('dashboard');

    Route::get('/fil', [FeedController::class, 'index'])->name('feed');
    Route::post('/publications', [PostController::class, 'store'])->name('posts.store');
    Route::get('/publications/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::delete('/publications/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/publications/{post}/jaime', [PostController::class, 'like'])->name('posts.like');
    Route::post('/publications/{post}/commentaires', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/commentaires/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/membres', [MemberController::class, 'index'])->name('members.index');
    Route::get('/membres/{user}', [MemberController::class, 'show'])->name('members.show');

    Route::get('/messages', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/messages/groupes', [ConversationController::class, 'store'])->name('conversations.store');
    Route::post('/messages/avec/{user}', [ConversationController::class, 'direct'])->name('conversations.direct');
    Route::get('/messages/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])->middleware('throttle:60,1')->name('messages.store');
    Route::get('/messages/{conversation}/nouveaux', [MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/messages/{conversation}/anciens', [MessageController::class, 'older'])->name('messages.older');
    Route::post('/messages/{conversation}/lu', [ConversationController::class, 'read'])->name('conversations.read');
    Route::post('/messages/{conversation}/quitter', [ConversationController::class, 'leave'])->name('conversations.leave');

    Route::get('/ma-cotisation', [DuesController::class, 'show'])->name('dues.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/tout-lu', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/ouvrir', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/push', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/notifications/push', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    Route::get('/reunions', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/reunions', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/reunions/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::post('/reunions/{meeting}/terminer', [MeetingController::class, 'end'])->name('meetings.end');
    Route::delete('/reunions/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    // Administration : bureau et administrateurs
    Route::middleware('can:manage-members')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/membres', [Admin\MemberController::class, 'index'])->name('members.index');
        Route::get('/membres/export', [Admin\MemberController::class, 'export'])->name('members.export');
        Route::get('/membres/pdf', [Admin\MemberController::class, 'pdf'])->name('members.pdf');
        Route::get('/membres/{user}', [Admin\MemberController::class, 'edit'])->name('members.edit');
        Route::put('/membres/{user}', [Admin\MemberController::class, 'update'])->name('members.update');
        Route::post('/membres/{user}/valider', [Admin\MemberController::class, 'approve'])->name('members.approve');
        Route::delete('/membres/{user}', [Admin\MemberController::class, 'destroy'])->name('members.destroy');

        Route::post('/membres/{user}/cotisations', [Admin\MembershipFeeController::class, 'store'])->name('fees.store');
        Route::get('/cotisations', [Admin\DuesController::class, 'index'])->name('dues.index');
        Route::get('/cotisations/export', [Admin\DuesController::class, 'export'])->name('dues.export');
        Route::get('/cotisations/pdf', [Admin\DuesController::class, 'pdf'])->name('dues.pdf');
        Route::post('/cotisations/relances', [Admin\DuesController::class, 'remindAll'])->name('dues.remind-all');
        Route::post('/membres/{user}/relance', [Admin\DuesController::class, 'remind'])->name('dues.remind');
        Route::delete('/cotisations/{fee}', [Admin\MembershipFeeController::class, 'destroy'])->name('fees.destroy');

        Route::get('/contacts', [Admin\ContactMessageController::class, 'index'])->name('contacts.index');
        Route::delete('/contacts/{contactMessage}', [Admin\ContactMessageController::class, 'destroy'])->name('contacts.destroy');

        Route::get('/site', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/site', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
