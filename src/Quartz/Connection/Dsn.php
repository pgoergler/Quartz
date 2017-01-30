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
        $matchs = \parse_url($dsn);
        if ($matchs === false)
        {
            throw new \Exception(sprintf('Cound not parse DSN "%s".', $dsn));
        }

        if (!isset($matchs['scheme']))
        {
            throw new \Exception(sprintf('No protocol information in dsn "%s".', $dsn));
        }
        $driver = $matchs['scheme'];


        if (!isset($matchs['host']))
        {
            throw new \Exception(sprintf('No host information in dsn "%s".', $dsn));
        }

        $host = $matchs['host'];
        $port = isset($matchs['port']) ? $matchs['port'] : null;

        if (!isset($matchs['user']))
        {
            throw new \Exception(sprintf('No user information in dsn "%s".', $dsn));
        }
        $user = $matchs['user'];
        $pass = isset($matchs['pass']) ? $matchs['pass'] : null;

        if (!isset($matchs['path']))
        {
            throw new \Exception(sprintf('No database name in dsn "%s".', $dsn));
        }

        if (preg_match('#^/(.*?)$#', $matchs['path'], $m))
        {
            $database = $m[1];
        } else
        {
            $database = $m['path'];
        }
        $parameters = array();
        isset($matchs['query']) ? \parse_str($matchs['query'], $parameters) : false;

        return array(
            'driver' => $driver,
            'host' => $host . ($port ? ':' . $port : ''),
            'user' => $user,
            'password' => $pass,
            'database' => $database,
            'parameters' => $parameters
        );
    }

}
