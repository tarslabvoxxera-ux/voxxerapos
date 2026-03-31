<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Security migration: Expire all remaining MD5-hashed employee passwords.
 *
 * Accounts that still have hash_version = '1' (MD5) have never logged in since the
 * bcrypt upgrade was introduced. We cannot safely re-hash them without knowing the
 * plain-text password, so we:
 *  1. Mark them as requiring a password reset by setting hash_version = '0' (locked).
 *  2. Blank the password field so MD5 comparison can never succeed.
 *  3. Log every affected account for the administrator.
 *
 * Administrators must then use "Forgot Password" or manually reset these accounts.
 * Rollback restores the hash_version to '1' but leaves the password blank (cannot restore MD5).
 */
class ExpireLegacyMD5Passwords extends Migration
{
    public function up(): void
    {
        // Find all accounts still on MD5 (hash_version = 1)
        $builder = $this->db->table('employees');
        $legacy_accounts = $builder->getWhere(['hash_version' => 1, 'deleted' => 0])->getResult();

        if (empty($legacy_accounts)) {
            log_message('notice', '[SECURITY MIGRATION] No legacy MD5 accounts found. Nothing to expire.');
            return;
        }

        foreach ($legacy_accounts as $account) {
            log_message('notice', "[SECURITY MIGRATION] Expiring legacy MD5 password for employee ID {$account->person_id}. Admin must reset this account.");
        }

        // Lock all remaining MD5 accounts:
        // hash_version '0' = locked/expired; login() will reject these until admin resets.
        $builder = $this->db->table('employees');
        $builder->where('hash_version', '1');
        $builder->where('deleted', 0);
        $builder->update([
            'hash_version' => '0',  // '0' = locked/expired
            'password'     => '',   // blank — no MD5 string can match an empty password hash
        ]);

        $count = count($legacy_accounts);
        log_message('notice', "[SECURITY MIGRATION] Expired {$count} legacy MD5 account(s). These accounts require a password reset before login.");
    }

    public function down(): void
    {
        // Rollback marks accounts as hash_version '1' again but leaves password blank.
        // Admins still need to set new passwords — this only undoes the version flag.
        $builder = $this->db->table('employees');
        $builder->where('hash_version', '0');
        $builder->update(['hash_version' => '1']);

        log_message('notice', '[SECURITY MIGRATION] Rollback: hash_version set back to 1 for expired accounts. Passwords remain blank — reset required.');
    }
}
