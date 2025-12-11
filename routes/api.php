<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReminderController;
use App\Jobs\SendReminderJob;
use App\Models\Reminder;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('reminders', [ReminderController::class, 'index']);
Route::post('reminders', [ReminderController::class, 'store']);

Route::post('/send-reminder/{id}', function ($id) {
    $reminder = Reminder::findOrFail($id);

    // Dispatch job
    SendReminderJob::dispatch($reminder);

    return response()->json([
        'status' => 'Job dispatched',
        'reminder_id' => $reminder->id
    ]);
});
