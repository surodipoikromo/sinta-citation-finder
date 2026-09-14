<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('source')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->string('source')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->timestamp('fetched_at')->nullable()->index();
        });
    }
    public function down(): void {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['source','external_id','fetched_at']);
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['source','source_url','synced_at']);
        });
    }
};
