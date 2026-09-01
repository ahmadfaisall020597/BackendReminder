<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReminderController;
use App\Jobs\SendReminderJob;
use App\Models\Reminder;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [AuthController::class, 'profile']);

    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::get('/reminders/pending', [ReminderController::class, 'filterReminderIsPending']);
    Route::get('/reminders/received', [ReminderController::class, 'filterReminderIsReceived']);
    Route::post('/send-reminder/{id}', function ($id) {

        $reminder = Reminder::findOrFail($id);

        SendReminderJob::dispatch($reminder);

        return response()->json([
            'status' => true,
            'message' => 'Job dispatched successfully',
            'reminder_id' => $reminder->id
        ]);
    });
});
