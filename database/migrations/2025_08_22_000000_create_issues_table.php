<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id(); // primary key
            $table->string('title'); // issue title
            $table->text('description'); // detailed description
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open'); // issue status
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium'); // issue priority
            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
