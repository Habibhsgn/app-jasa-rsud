<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropForeign('index_scorings_source_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->foreign('source_id', 'index_scorings_source_id_foreign')
                ->references('id')->on('top_leaders')
                ->onDelete('set null');
        });
    }

};
