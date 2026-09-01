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
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya administrator yang dapat membuat reminder.'
            ], 403);
        }

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

    public function filterReminderIsPending(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $reminders = Reminder::where('sent', false)
                ->orderBy('reminder_at', 'asc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data reminder pending berhasil diambil.',
                'data' => $reminders->items(),

                'pagination' => [
                    'current_page' => $reminders->currentPage(),
                    'last_page' => $reminders->lastPage(),
                    'per_page' => $reminders->perPage(),
                    'total' => $reminders->total(),
                    'from' => $reminders->firstItem(),
                    'to' => $reminders->lastItem(),
                    'next_page_url' => $reminders->nextPageUrl(),
                    'prev_page_url' => $reminders->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil reminder pending.',
            ], 500);
        }
    }

    public function filterReminderIsReceived(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);

            $reminders = Reminder::where('sent', true)
                ->orderBy('reminder_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data reminder berhasil dikirim.',
                'data' => $reminders->items(),

                'pagination' => [
                    'current_page' => $reminders->currentPage(),
                    'last_page' => $reminders->lastPage(),
                    'per_page' => $reminders->perPage(),
                    'total' => $reminders->total(),
                    'from' => $reminders->firstItem(),
                    'to' => $reminders->lastItem(),
                    'next_page_url' => $reminders->nextPageUrl(),
                    'prev_page_url' => $reminders->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil reminder yang sudah dikirim.',
            ], 500);
        }
    }
}
