<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('sinta_level')->index();
            $table->string('issn')->nullable();
            $table->string('eissn')->nullable();
            $table->string('subject_area')->nullable()->index();
            $table->text('website_url')->nullable();
            $table->timestamps();
            $table->unique(['name','sinta_level']);
        });
    }
    public function down(): void { Schema::dropIfExists('journals'); }
};
