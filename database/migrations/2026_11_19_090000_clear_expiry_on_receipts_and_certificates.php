<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// An official receipt and the DOLE/SEC certificate carry no validity date (config/document_types.php,
// 'expires' => false), so the form no longer asks for one. Dates entered on them before are cleared,
// so they don't show as "expiring" or count against the document-currency score.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')->whereIn('document_type', ['fee_receipt', 'dole_sec_certification'])->update(['expires_at' => null]);
    }

    public function down(): void
    {
        // The cleared dates are not restored.
    }
};
