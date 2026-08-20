<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deletes a user and everything that belongs to them.
 *
 * The cascade is defined in database/sql/delete_user_cascade.sql — a vetted,
 * ordered script that nulls out references that should merely detach and deletes
 * the rows that should go with the account, leaves first and the user row last.
 * We drive it from PHP (rather than relying on database foreign keys) because the
 * production database is TiDB, where FK constraints are not enforced, so the
 * clean-up has to be explicit and identical across environments.
 */
class AccountDeletionService
{
    /**
     * Delete the given user and all of their related records in one transaction.
     */
    public function delete(User $user): void
    {
        $id = (int) $user->id;

        DB::transaction(function () use ($id, $user) {
            // Sanctum tokens are a polymorphic relation with no FK, so the cascade
            // script can't reach them — revoke them here.
            $user->tokens()->delete();

            // Bind the target once; every statement in the script keys off @uid.
            DB::statement('SET @uid = '.$id);

            foreach ($this->statements() as $statement) {
                DB::statement($statement);
            }
        });
    }

    /**
     * The individual SQL statements from the cascade script, in order, with the
     * comments and the placeholder `SET @uid` line stripped out.
     *
     * @return array<int, string>
     */
    private function statements(): array
    {
        $sql = file_get_contents(database_path('sql/delete_user_cascade.sql'));

        // Drop comment lines so they don't end up glued onto a statement.
        $lines = array_filter(
            explode("\n", $sql),
            fn (string $line) => ! str_starts_with(trim($line), '--'),
        );

        $clean = implode("\n", $lines);

        return array_values(array_filter(
            array_map('trim', explode(";", $clean)),
            // Skip blanks and the placeholder SET line — we set @uid ourselves.
            fn (string $s) => $s !== '' && ! str_starts_with(strtoupper($s), 'SET @UID'),
        ));
    }
}
