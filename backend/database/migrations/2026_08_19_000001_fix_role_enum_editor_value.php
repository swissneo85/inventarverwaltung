<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 2026_06_12_000001_change_role_enum_user_to_editor hat nur die Daten
     * (role='user' -> 'editor') migriert, aber die DB-seitige Wertemenge der
     * Spalte (MySQL ENUM bzw. SQLite CHECK-Constraint) nie nachgezogen.
     * Dadurch schlug das Anlegen/Speichern von role='editor' auf DB-Ebene fehl,
     * obwohl 'editor' im Model (User::ROLE_EDITOR) und der Validierung längst
     * der gültige Wert ist.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteRoleColumn(['admin', 'editor', 'viewer'], 'editor');
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'editor'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteRoleColumn(['admin', 'user', 'viewer'], 'user');
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'viewer') NOT NULL DEFAULT 'user'");
        }
    }

    /**
     * SQLite emuliert enum() als VARCHAR + CHECK-Constraint, der sich nicht
     * per ALTER COLUMN ändern lässt. Die Spalte muss daher unter neuem
     * Namen mit korrektem CHECK neu angelegt, befüllt und umbenannt werden.
     */
    private function rebuildSqliteRoleColumn(array $allowedRoles, string $default): void
    {
        Schema::table('users', function (Blueprint $table) use ($allowedRoles, $default) {
            $table->enum('role_new', $allowedRoles)->default($default)->after('role');
        });

        DB::statement('UPDATE users SET role_new = role');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }
};
