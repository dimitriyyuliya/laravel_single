<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->index('title');
            $table->string('slug')->unique();
            $table->string('status', 100)->default(config('add.page_statuses')[0] ?? 'inactive');
            $table->float('price')->nullable();
            $table->float('old_price')->nullable();
            $table->smallInteger('sort')->unsigned()->default('500');
            $table->string('weight')->nullable();
            $table->string('units')->default(config('shop.units')[0]);
            $table->text('description')->nullable();
            $table->text('body')->nullable();
            $table->string('img')->nullable()->default(config('admin.imgProductDefault'));
            $table->integer('popular')->unsigned()->default('0');
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
        Schema::dropIfExists('products');
    }
}
