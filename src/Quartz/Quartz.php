<?php

namespace Quartz;

/**
 * Description of Quartz
 *
 * @author paul
 */
class Quartz
{

    protected $tables = array();
    protected $configs = array();
    protected $connections = array();
    protected $initialized = false;
    protected static $_instance = null;

    protected function __construct()
    {

    }

    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     *
     * @param array $configs
     * @return \Quartz\Quartz
     */
    public function init(array $configs = array())
    {
        $this->configs = $configs;
        $this->initialized = true;
        return $this;
    }

    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     *
     * @return \Quartz\Quartz
     */
    public static function &getInstance()
    {
        if (is_null(static::$_instance))
        {
            $class = get_called_class();
            static::$_instance = new $class();
        }
        return static::$_instance;
    }

    /**
     *
     * @param string $className
     * @return \Quartz\Object\Table
     */
    public function &getTable($className)
    {
        if (!$this->hasTable($className))
        {
            $obj = new $className();
            $this->setTable($className, $obj->getTable());
        }
        return $this->instances[$className];
    }

    /**
     *
     * @param string $className
     * @param \Quartz\Object\Table $table
     * @return \Quartz\Quartz
     */
    public function setTable($className, \Quartz\Object\Table &$table)
    {
        $this->instances[$className] = $table;
        return $this;
    }

    public function hasTable($className)
    {
        return isset($this->instances[$className]);
    }

    public function setConnection($name, Connection\Connection &$connetion)
    {
        $this->connections[$name] = $connection;
    }

    /**
     *
     * @param String $database_config_name
     * @return \Quartz\Connection\Connection
     */
    public function &getConnection($name)
    {
        if (isset($this->connections[$name]))
        {
            return $this->connections[$name];
        } else
        {
            if (isset($this->configs[$name]) && is_array($this->configs[$name]))
            {
                $config = $this->process($this->configs[$name]);
                $this->configs[$name] = array_merge($this->configs[$name], $config);

                $driver = $this->configs[$name]['driver'];

                $conn = new $driver($this->configs[$name]['host'],
                                $this->configs[$name]['user'],
                                $this->configs[$name]['password'],
                                $this->configs[$name]['database'],
                                $this->configs[$name]['extra']);

                //$conn = $this->__createDatabase($this->configs[$name]);
                $conn->connect();
                $this->connections[$name] = $conn;
                return $this->connections[$name];
            } else
            {
                throw new \InvalidArgumentException('No configuration found for database [' . $name . ']');
            }
        }
    }

    public function closeAll()
    {
        foreach( $this->connections as $name => $conn)
        {
            $conn->close();
        }
    }

    public function getDatabaseName($name)
    {
        if (!isset($this->configs[$name]) || !is_array($this->configs[$name]))
        {
            throw new \InvalidArgumentException('No configuration found for database [' . $name . ']');
        }
        return $this->configs[$name]['database'];
    }

    private function process($configuration)
    {
        $config = $configuration;

        $extra = array();
        if (isset($configuration['dsn']))
        {
            $config = Connection\Connection::processDsn($configuration['dsn']);
            if (isset($configuration['extra']))
            {
                $extra = $configuration['extra'];
            }
        }

        if (isset($configuration['quartz.classname']))
        {
            $driver = $configuration['quartz.classname'];
        } else
        {
            $driver = '\\Quartz\\Connection\\' . ucfirst($config['driver']) . 'Connection';
        }

        $extra = array_merge($extra, array(
            'persistant' => isset($configuration['persistant']) ? $configuration['persistant'] : false,
                ));

        return array(
            'driver' => $driver,
            'host' => $config['host'],
            'user' => $config['user'],
            'password' => $config['password'],
            'database' => $config['database'],
            'extra' => $extra,
        );
    }

}