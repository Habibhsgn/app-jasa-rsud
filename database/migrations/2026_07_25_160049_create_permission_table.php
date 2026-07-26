<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();   // dipakai sama dengan Route::name(), mis. 'jasa.index'
            $table->string('name');             // label untuk UI, mis. 'Master Input Jasa'
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
