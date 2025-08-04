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
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->string('type')->default('text'); // text, image, file, etc.
            $table->json('attachments')->nullable();
            $table->foreignId('reply_to')->nullable()->constrained('group_messages')->onDelete('set null');
            $table->timestamps();
        });

        // Add indexes for better performance
        Schema::table('group_messages', function (Blueprint $table) {
            $table->index(['group_id', 'created_at']);
            $table->index('user_id');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->index('group_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_messages');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
    }
};
