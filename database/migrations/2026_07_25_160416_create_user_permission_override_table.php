<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permission_override', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            // grant  = kasih akses tambahan di luar role user ini
            // revoke = cabut akses yang harusnya didapat dari role user ini
            $table->enum('type', ['grant', 'revoke']);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_override');
    }
};
