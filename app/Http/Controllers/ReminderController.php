<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Jobs\SendReminderJob;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Reminder::all()
        ]);
    }

    // noted jika tidak terkirim jalankan php artisan queue:work karena bisa jadi jobsnya antriannya banyak.
    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'message' => 'required',
            'reminder_at' => 'required|date'
        ], [
            'phone.required' => 'Nomor WA wajib diisi',
            'message.required' => 'Pesan wajib diisi',
            'reminder_at.required' => 'Waktu reminder wajib diisi'
        ]);

        try {
            $reminder = Reminder::create($request->all());

            $reminderTime = Carbon::parse($reminder->reminder_at)->setTimezone('Asia/Jakarta');
            $now = Carbon::now('Asia/Jakarta');
            $delaySeconds = $now->diffInSeconds($reminderTime, false);

            if ($delaySeconds > 0) {
                SendReminderJob::dispatch($reminder)->delay($delaySeconds);
                Log::info("Reminder job scheduled in {$delaySeconds} seconds (delayed).");
            } else {
                SendReminderJob::dispatch($reminder);
                Log::info("Reminder job dispatched immediately (past time).");
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Reminder berhasil disimpan dan akan dikirim ke WhatsApp pada waktu yang ditentukan.',
                'data' => $reminder
            ], 201);
        } catch (QueryException $e) {
            Log::error('Gagal menyimpan reminder: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan reminder',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
