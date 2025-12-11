<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('phone'); // nomor WA
            $table->text('message'); // pesan
            $table->dateTime('reminder_at'); // waktu reminder
            $table->boolean('sent')->default(false); // sudah dikirim atau belum
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
