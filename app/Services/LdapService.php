<?php

namespace App\Services;

use App\Exceptions\Ldap\LdapAuthenticationException;
use App\Exceptions\Ldap\LdapConnectionException;
use App\Exceptions\Ldap\LdapUserNotFoundException;
use App\Interfaces\LdapInterface;
use Illuminate\Support\Facades\Log;


class LdapService implements LdapInterface
{
    public function loginLdap(string $username, string $password): array
    {
        $host = config('ldap.host');
        $domain = config('ldap.domain');
        $baseDn = config('ldap.base');

        $connection = @ldap_connect($host);

        if (!$connection) {
            Log::error('Falha na comunicação com o servidor LDAP.');
            throw new LdapConnectionException();
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        $ldap = @ldap_bind($connection, "{$domain}\\{$username}", $password);

        if (!$ldap) {
            Log::warning("Falha de autenticação para o usuário: {$username}");
            throw new LdapAuthenticationException();
        }

        $search = ldap_search($connection, $baseDn, "sAMAccountName={$username}");
        $results = ldap_get_entries($connection, $search);

        if (empty($results[0]['mail'][0])) {
            throw new LdapUserNotFoundException();
        }

        $ldap_mail = $results[0]['mail'][0];
        $ldap_name = $results[0]['cn'][0];
        $ldap_login = strtoupper($results[0]['samaccountname'][0]);

        return ['email' => $ldap_mail, 'name' => $ldap_name, 'login' => $ldap_login];
    }
}
