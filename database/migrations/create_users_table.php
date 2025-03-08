<?php

declare(strict_types=1);

use Flux\Database\Migration;
use Flux\Database\Schema;
use Flux\Database\Table;

class CreateUsersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        Schema::create('users', function(Table $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('provider')->default('native');
            $table->string('provider_id')->nullable();
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}

