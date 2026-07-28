<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill action_logs.order_number for existing accessory / consumable /
 * component create + update entries. Uses the item's CURRENT order_number,
 * so any log whose item has since been edited will pick up the new value
 * rather than the value at the time of the log. Acceptable trade-off for
 * a one-time historical fix; new log entries carry the accurate value
 * because the observers now capture it at write time.
 *
 * Only touches rows where order_number is null so re-running the migration
 * is a no-op and doesn't clobber values set by the observer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $prefix = DB::getTablePrefix();

        foreach ([
            \App\Models\Accessory::class => 'accessories',
            \App\Models\Consumable::class => 'consumables',
            \App\Models\Component::class => 'components',
        ] as $modelClass => $table) {
            DB::table('action_logs')
                ->where('item_type', $modelClass)
                ->whereIn('action_type', ['create', 'update'])
                ->whereNull('order_number')
                ->update([
                    'order_number' => DB::raw(
                        "(SELECT order_number FROM {$prefix}{$table} "
                        ."WHERE {$prefix}{$table}.id = {$prefix}action_logs.item_id "
                        .'LIMIT 1)'
                    ),
                ]);
        }
    }

    public function down(): void
    {
        // No-op: we can't distinguish backfilled rows from ones the
        // observer wrote after the fact, so undoing would risk erasing
        // real order references. If you truly need to reverse, run the
        // schema-drop migration instead (which drops the column).
    }
};
