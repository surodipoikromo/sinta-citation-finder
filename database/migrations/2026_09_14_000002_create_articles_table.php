<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->text('authors');
            $table->text('title');
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('doi')->nullable()->index();
            $table->text('url')->nullable();
            $table->longText('abstract');
            $table->text('keywords')->nullable();
            $table->timestamps();
            $table->index('journal_id');
        });
    }
    public function down(): void { Schema::dropIfExists('articles'); }
};
