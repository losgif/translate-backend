<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslationGeneratesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('translation_generates', static function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->comment('标题');
            $table->string('desc', 256)->comment('中文导读');
            $table->string('image')->comment('图片');
            $table->text('original_text')->comment('原文');
            $table->json('words')->comment('单词');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('translation_generates');
    }
}
