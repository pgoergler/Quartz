<?php

namespace Quartz\Connection;

/**
 *
 * @author paul
 */
interface Connection
{

    public function getDatabaseName();

    public function connect();

    public function close($force = false);

    public function isClosed();

    public function &getRaw();

    public function query($query, $parameters = array());

    public function error();

    public function errorCode();

    public function begin();

    public function commit();

    public function rollback($force = false);

    public function savepoint($savepoint);

    public function commitSavepoint($savepoint);

    public function rollbackSavepoint($savepoint);

    public function fetchRow($resource, $index = null);

    public function free($resource);

    public function countRows($resource);

    public function escapeField($field);

    public function escape($value, $type = 'string');

    public function escapeBinary($value);

    public function escapeString($value);

    public function escapeNumber($value, $type = 'integer');

    public function convertType($type);

    /**
     * registerConverter
     *
     * Register a new converter
     * @access public
     * @param  String             $name      The name of the converter.
     * @param  ConverterInterface $converter A converter instance.
     * @param  Array              $pg_types  An array of the mapped postgresql's types.
     * @return Quartz\Connection\Connection
     */
    public function registerConverter($name, \Quartz\Converter\Converter $converter, array $types = array());

    /**
     * getConverterFor
     *
     * Returns a converter from its designation.
     *
     * @access public
     * @param  string $name       Converter desgination.
     * @return \Quartz\Converter\Converter Converter instance.
     * */
    public function getConverterFor($name);

    /**
     * getConverterForType
     *
     * Returns the converter instance for a given a postgresql's type
     *
     * @access public
     * @param  String $type Type name.
     * @return \Quartz\Converter\Converter Converter instance.
     * @throw  \RuntimeException if not found.
     * */
    public function getConverterForType($type);

    /**
     * registerTypeForConverter
     *
     * Associate an existing converter with a Db type.
     * This is useful for DOMAINs.
     *
     * @acces public
     * @param String $type           Type name
     * @param String $converter_name Converter designation.
     * @return Quartz\Connection\Connection
     * */
    public function registerTypeForConverter($type, $converter_name);

    public function create(\Quartz\Object\Table $table);

    public function drop(\Quartz\Object\Table $table, $cascade = false);

    /**
     * 
     * @param type $table
     * @param type $object
     * @param type $returning
     * @return \Quartz\Object\Collection
     */
    public function insert($table, $object, $returning = '*');

    /**
     * 
     * @param type $table
     * @param type $object
     * @param type $returning
     * @return \Quartz\Object\Collection
     */
    public function update($table, $query, $object, $returning = '*', $options = array());

    /**
     * 
     * @param type $table
     * @param type $object
     * @param type $returning
     * @return \Quartz\Object\Collection
     */
    public function delete($tableName, $query, $returning = '*', $options = array());

    /**
     * 
     * @param type $table
     * @param array $criteria
     * @param type $order
     * @param type $limit
     * @param type $offset
     * @param type $forUpdate
     * @return \Quartz\Object\Collection
     */
    public function find($table, array $criteria = array(), $order = null, $limit = null, $offset = 0, $forUpdate = false);

    public function convertFromDb($value, $type);

    public function convertToDb($value, $type);
}
