<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('thread_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->timestamps();

            // Un utilisateur ne peut suivre un thread qu'une seule fois
            $table->unique(['user_id', 'thread_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_subscriptions');
    }
};
