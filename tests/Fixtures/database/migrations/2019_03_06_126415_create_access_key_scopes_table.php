<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessKeyScopesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_key_scopes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('scope');
            $table->unsignedBigInteger('access_key_id');
            $table->foreign('access_key_id')->references('id')->on('access_keys')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_key_scopes');
    }
}
