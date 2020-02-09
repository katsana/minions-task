<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMinionTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(\config('minions-task.table'), function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('creator');

            $table->string('project');
            $table->string('method');
            $table->longText('payload')->nullable();
            $table->longText('exception')->nullable();

            $table->string('status')->default('waiting')->index();

            $table->timestamps();

            $table->index(['project', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(\config('minions-task.table'));
    }
}
