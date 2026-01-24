<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table with the new enum values
        // For MySQL, we can alter the column directly
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for enums
            // We need to create a new table and migrate data
            Schema::table('agent_memories', function (Blueprint $table) {
                // Drop the scope column and recreate it
                // SQLite workaround: create temporary column
            });

            // For SQLite testing, we can use a raw statement to recreate the table
            DB::statement('PRAGMA foreign_keys=off');

            // Create the new table with updated enum
            DB::statement('
                CREATE TABLE agent_memories_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    team_id INTEGER NOT NULL,
                    ai_agent_id INTEGER,
                    scope TEXT CHECK(scope IN (\'project\', \'client\', \'org\', \'chain\')) NOT NULL,
                    scope_type TEXT NOT NULL,
                    scope_id INTEGER NOT NULL,
                    key TEXT NOT NULL,
                    value TEXT NOT NULL,
                    expires_at TEXT,
                    created_at TEXT,
                    updated_at TEXT,
                    deleted_at TEXT,
                    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
                    FOREIGN KEY (ai_agent_id) REFERENCES ai_agents(id) ON DELETE SET NULL
                )
            ');

            // Copy data from old table to new table
            DB::statement('
                INSERT INTO agent_memories_new
                SELECT id, team_id, ai_agent_id, scope, scope_type, scope_id, key, value, expires_at, created_at, updated_at, deleted_at
                FROM agent_memories
            ');

            // Drop old table
            DB::statement('DROP TABLE agent_memories');

            // Rename new table to old name
            DB::statement('ALTER TABLE agent_memories_new RENAME TO agent_memories');

            // Recreate indexes
            DB::statement('CREATE INDEX agent_memories_scope_lookup ON agent_memories(team_id, scope, scope_type, scope_id, key)');
            DB::statement('CREATE INDEX agent_memories_team_id_ai_agent_id_index ON agent_memories(team_id, ai_agent_id)');
            DB::statement('CREATE INDEX agent_memories_expires_at_index ON agent_memories(expires_at)');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // MySQL can handle enum modification
            DB::statement("ALTER TABLE agent_memories MODIFY COLUMN scope ENUM('project', 'client', 'org', 'chain') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            DB::statement('
                CREATE TABLE agent_memories_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    team_id INTEGER NOT NULL,
                    ai_agent_id INTEGER,
                    scope TEXT CHECK(scope IN (\'project\', \'client\', \'org\')) NOT NULL,
                    scope_type TEXT NOT NULL,
                    scope_id INTEGER NOT NULL,
                    key TEXT NOT NULL,
                    value TEXT NOT NULL,
                    expires_at TEXT,
                    created_at TEXT,
                    updated_at TEXT,
                    deleted_at TEXT,
                    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
                    FOREIGN KEY (ai_agent_id) REFERENCES ai_agents(id) ON DELETE SET NULL
                )
            ');

            // Copy data (excluding chain scope records)
            DB::statement('
                INSERT INTO agent_memories_new
                SELECT id, team_id, ai_agent_id, scope, scope_type, scope_id, key, value, expires_at, created_at, updated_at, deleted_at
                FROM agent_memories
                WHERE scope != \'chain\'
            ');

            DB::statement('DROP TABLE agent_memories');
            DB::statement('ALTER TABLE agent_memories_new RENAME TO agent_memories');

            DB::statement('CREATE INDEX agent_memories_scope_lookup ON agent_memories(team_id, scope, scope_type, scope_id, key)');
            DB::statement('CREATE INDEX agent_memories_team_id_ai_agent_id_index ON agent_memories(team_id, ai_agent_id)');
            DB::statement('CREATE INDEX agent_memories_expires_at_index ON agent_memories(expires_at)');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            DB::statement("ALTER TABLE agent_memories MODIFY COLUMN scope ENUM('project', 'client', 'org') NOT NULL");
        }
    }
};
