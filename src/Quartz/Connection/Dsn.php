<?php

namespace Quartz\Connection;

/**
 * Description of Dsn
 *
 * @author paul
 */
class Dsn
{

    /**
     * extractDsn
     * Sets the different parameters from the DSN
     *
     * @access protected
     * @return void
     */
    public static function extract($dsn)
    {
        if (!preg_match('#(?P<driver>[a-z]+)://(?P<user>[^:@]+)(?::(?P<password>[^@]+))?(?:@(?P<host>[\-\w\.]+|!/.+[^/]!)(?::(\w+))?)?/(?P<database>\w+)#', $dsn, $matchs))
        {
            throw new \Exception(sprintf('Cound not parse DSN "%s".', $dsn));
        }

        if ($matchs['driver'] == null)
        {
            throw new \Exception(sprintf('No protocol information in dsn "%s".', $dsn));
        }
        $driver = $matchs['driver'];

        if ($matchs['user'] == null)
        {
            throw PommException(sprintf('No user information in dsn "%s".', $dsn));
        }
        $user = $matchs['user'];
        $pass = $matchs['password'];

        if (preg_match('/!(.*)!/', $matchs['host'], $host_matchs))
        {
            $host = $host_matchs[1];
        } else
        {
            $host = $matchs['host'];
        }

        $port = $matchs[5];

        if ($matchs['database'] == null)
        {
            throw new \Exception(sprintf('No database name in dsn "%s".', $dsn));
        }
        $database = $matchs['database'];

        return array(
            'driver' => $driver,
            'host' => $host . ($port ? ':' . $port : ''),
            'user' => $user,
            'password' => $pass,
            'database' => $database,
        );
    }

}
