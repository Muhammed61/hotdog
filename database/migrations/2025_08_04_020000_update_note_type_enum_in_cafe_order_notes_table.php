<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateNoteTypeEnumInCafeOrderNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Raw SQL ile enum değiştir
        DB::statement("ALTER TABLE cafe_order_notes MODIFY COLUMN note_type ENUM('initial', 'additional', 'status_change') NOT NULL DEFAULT 'initial'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE cafe_order_notes MODIFY COLUMN note_type ENUM('initial', 'additional') NOT NULL DEFAULT 'initial'");
    }
}