<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// Check stock locations
echo "=== stock_locations ===\n";
$locs = DB::table('stock_locations')->get();
foreach($locs as $l) echo "  id:{$l->id} name:{$l->name} type:{$l->type}\n";

// Check stock_balances for drug-linked products
echo "\n=== stock_balances (drug-linked products) ===\n";
$balances = DB::table('stock_balances as sb')
    ->join('drugs as d','d.product_id','=','sb.product_id')
    ->select('d.id as drug_id','d.name','sb.quantity_on_hand','sb.stock_location_id','sb.product_id')
    ->get();
foreach($balances as $r) echo "  drug_id:{$r->drug_id} name:{$r->name} qty:{$r->quantity_on_hand} loc_id:{$r->stock_location_id}\n";
if($balances->isEmpty()) echo "  none\n";

// Check drugs with product_id
echo "\n=== drugs with product_id set ===\n";
$cnt = DB::table('drugs')->whereNotNull('product_id')->count();
echo "  count: $cnt\n";
