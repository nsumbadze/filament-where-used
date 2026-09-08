<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('promotable');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->json('category_ids')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
};
