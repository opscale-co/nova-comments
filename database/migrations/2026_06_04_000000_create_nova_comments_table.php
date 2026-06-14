<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_comments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('commentable');
            $table->unsignedBigInteger('commenter_id')->nullable();
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_comments');
    }
};
