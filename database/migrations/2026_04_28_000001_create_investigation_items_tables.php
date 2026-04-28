<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Investigation item catalog (reagents, test kits, consumables, radiology supplies, etc.)
        Schema::create('investigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('category')->default('other'); // InvestigationItemCategory enum
            $table->string('unit')->default('unit');      // e.g. vial, box, strip
            $table->integer('reorder_level')->default(10);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // Stock ledger for investigation items (mirrors drug_stock structure)
        Schema::create('investigation_item_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_item_id')->constrained('investigation_items')->cascadeOnDelete();
            $table->string('location')->default('laboratory'); // StockLocation enum
            $table->string('batch_number')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->date('received_date')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('reorder_level')->default(10);
            $table->timestamps();
        });

        // Add investigation_item_id to purchase_order_items (nullable, either drug or inv item)
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('investigation_item_id')
                  ->nullable()
                  ->after('drug_id')
                  ->constrained('investigation_items')
                  ->nullOnDelete();
            $table->string('item_type')->default('drug')->after('investigation_item_id'); // 'drug' | 'investigation'
        });

        // Add investigation_item_id to stock_transfer_items
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->foreignId('investigation_item_id')
                  ->nullable()
                  ->after('drug_id')
                  ->constrained('investigation_items')
                  ->nullOnDelete();
            $table->string('item_type')->default('drug')->after('investigation_item_id'); // 'drug' | 'investigation'
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['investigation_item_id']);
            $table->dropColumn(['investigation_item_id', 'item_type']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['investigation_item_id']);
            $table->dropColumn(['investigation_item_id', 'item_type']);
        });

        Schema::dropIfExists('investigation_item_stock');
        Schema::dropIfExists('investigation_items');
    }
};
