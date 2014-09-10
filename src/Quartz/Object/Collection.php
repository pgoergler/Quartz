<?php

namespace Quartz\Object;

/**
 * Description of Collection
 *
 * @author paul
 */
class Collection implements \Iterator, \Countable
{

    protected $filters;
    protected $connection;
    protected $result;
    protected $position = 0;

    public function __construct(\Quartz\Connection\Connection &$connection, $result)
    {
        $this->connection = $connection;
        $this->result = $result;
        $this->clearFilters();
    }

    public function __destruct()
    {
        if ($this->result)
        {
            $this->connection->free($this->result);
        }
    }

    public function count()
    {
        if( $this->result )
        {
            return $this->connection->countRows($this->result);
        }
        return 0;
    }

    public function current()
    {
        return $this->get($this->position);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return $this->has($this->position);
    }

    /**
     * isFirst
     * Is the iterator on the first element ?
     *
     * @return Boolean
     */
    public function isFirst()
    {
        return $this->position === 0;
    }

    /**
     * isLast
     *
     * Is the iterator on the last element ?
     *
     * @return Boolean
     */
    public function isLast()
    {
        return $this->position === $this->count() - 1;
    }

    /**
     * isEmpty
     *
     * Is the collection empty (no element) ?
     *
     * @return Boolean
     */
    public function isEmpty()
    {
        return pg_num_rows($this->result_resource) === 0;
    }

    /**
     * isEven
     *
     * Is the iterator on an even position ?
     *
     * @return Boolean
     */
    public function isEven()
    {
        return ($this->position % 2) === 0;
    }

    /**
     * isOdd
     *
     * Is the iterator on an odd position ?
     *
     * @return Boolean
     */
    public function isOdd()
    {
        return ($this->position % 2) === 1;
    }

    public function get($index)
    {
        if( !$this->result )
        {
            return false;
        }
        
        $values = $this->connection->fetchRow($this->result, $index);

        if ($values === false)
        {
            return false;
        }

        foreach ($this->filters as $index => $filter)
        {
            $values = call_user_func($filter, $values);
        }

        return $values;
    }
    
    /**
     * has
     *
     * Return true if the given index exists false otherwise.
     *
     * @param Integer $index
     * @return Boolean
     */
    public function has($index)
    {
        return $index < $this->count();
    }


    /**
     * registerFilter
     *
     * Register a new callable filter. All filters MUST return an associative 
     * array with field name as key.
     * @param Callable $callable the filter.
     */
    public function registerFilter($callable)
    {
        if (!is_callable($callable))
        {
            throw new \Exception(sprintf("Given filter is not a callable (type '%s').", gettype($callable)));
        }

        $this->filters[] = $callable;
    }

    /**
     * clearFilters
     *
     * Empty the filter stack.
     */
    public function clearFilters()
    {
        $this->filters = array();
    }

}
