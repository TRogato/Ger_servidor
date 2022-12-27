<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIncludeWwwToProjectTables extends Migration
{
    public function up(): void
    {
        Schema::table('project_applications', static function (Blueprint $table): void {
            $table->boolean('include_www')->default(false);
        });
        Schema::table('project_redirects', static function (Blueprint $table): void {
            $table->boolean('include_www')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('project_applications', static function (Blueprint $table): void {
            $table->dropColumn('include_www');
        });
        Schema::table('project_redirects', static function (Blueprint $table): void {
            $table->dropColumn('include_www');
        });
    }
}
