<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('guest_id')
                ->constrained('hotel_organizations')
                ->nullOnDelete();
            $table->index('organization_id');
        });

        // Backfill: every non-customer user already linked to branches belongs
        // to those branches' organization — except super admins, who stay
        // platform-level (organization_id = NULL = sees everything).
        // Nowdoc keeps the double backslashes MySQL needs to match the
        // stored morph class 'App\Models\User' literally.
        DB::statement(<<<'SQL'
            UPDATE users u
            INNER JOIN (
                SELECT ub.user_id, MIN(b.organization_id) AS organization_id
                FROM user_branches ub
                INNER JOIN hotel_branches b ON b.id = ub.branch_id
                GROUP BY ub.user_id
            ) linked ON linked.user_id = u.id
            SET u.organization_id = linked.organization_id
            WHERE u.user_type <> 'customer'
              AND NOT EXISTS (
                  SELECT 1
                  FROM model_has_roles mhr
                  INNER JOIN roles r ON r.id = mhr.role_id
                  WHERE mhr.model_type = 'App\\Models\\User'
                    AND mhr.model_id = u.id
                    AND r.name = 'super-admin'
              )
        SQL);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
