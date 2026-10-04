<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla category
        Schema::create('category', function (Blueprint $table) {
            $table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
            $table->string('name', 120)->unique('uq_category_name');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE category ADD CONSTRAINT ck_category_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
        }

        // 2. Tabla product
        Schema::create('product', function (Blueprint $table) {
            $table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
            $table->string('name', 200);
            $table->decimal('price', 12, 2);
            $table->integer('stock');
            $table->char('category_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('image_key', 512)->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->integer('version')->default(1);

            $table->foreign('category_id', 'fk_product_category_id')
                ->references('id')->on('category')
                ->onDelete('restrict')->onUpdate('no action');

            $table->index(['category_id', 'deleted_at', 'name'], 'idx_product_category_active_name');
            $table->index(['deleted_at', 'name'], 'idx_product_active_name');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product ADD CONSTRAINT ck_product_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
            DB::statement('ALTER TABLE product ADD CONSTRAINT ck_product_price_positive CHECK (price > 0)');
            DB::statement('ALTER TABLE product ADD CONSTRAINT ck_product_stock_non_negative CHECK (stock >= 0)');
        }

        // 3. Tabla user
        Schema::create('user', function (Blueprint $table) {
            $table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
            $table->string('username', 120)->unique('uq_user_username');
            $table->string('password_hash', 512)->charset('ascii')->collation('ascii_bin');
            $table->string('role', 40)->charset('ascii')->collation('ascii_bin');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `user` ADD CONSTRAINT ck_user_username_normalized CHECK (CHAR_LENGTH(username) > 0 AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY))');
            DB::statement('ALTER TABLE `user` ADD CONSTRAINT ck_user_role_allowed CHECK (role IN (\'admin\',\'seller\'))');
            DB::statement('ALTER TABLE `user` ADD CONSTRAINT ck_user_password_hash_not_blank CHECK (CHAR_LENGTH(password_hash) > 0)');
        }

        // 4. Tabla sale
        Schema::create('sale', function (Blueprint $table) {
            $table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
            $table->dateTime('sold_at', 6);
            $table->string('sold_by_username', 120);
            $table->char('sold_by_user_id', 36)->charset('ascii')->collation('ascii_bin');

            $table->foreign('sold_by_user_id', 'fk_sale_sold_by_user_id')
                ->references('id')->on('user')
                ->onDelete('restrict')->onUpdate('no action');

            $table->index('sold_at', 'idx_sale_sold_at');
            $table->index('sold_by_user_id', 'idx_sale_sold_by_user_id');
        });

        // 5. Tabla sale_item
        Schema::create('sale_item', function (Blueprint $table) {
            $table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
            $table->char('sale_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('product_name', 200);
            $table->string('category_name', 120);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);

            $table->foreign('sale_id', 'fk_sale_item_sale_id')
                ->references('id')->on('sale')
                ->onDelete('cascade')->onUpdate('no action');

            $table->foreign('product_id', 'fk_sale_item_product_id')
                ->references('id')->on('product')
                ->onDelete('restrict')->onUpdate('no action');

            $table->unique(['sale_id', 'product_id'], 'uq_sale_item_sale_product');
            $table->index('product_id', 'idx_sale_item_product_id');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT ck_sale_item_quantity_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE sale_item ADD CONSTRAINT ck_sale_item_unit_price_positive CHECK (unit_price > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item');
        Schema::dropIfExists('sale');
        Schema::dropIfExists('user');
        Schema::dropIfExists('product');
        Schema::dropIfExists('category');
    }
};
