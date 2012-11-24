<?php
namespace Acorn\Database;

/**
 * 	database.php
 * 	Holds the database class.
 * 	Database
 *
 * 	Updated 2012.07.17 to use MySQLi over MySQL.
 */
class Database
{
	protected $instance;
	protected $nsProcedure;
	protected $nsEntities;

	public function __construct($connstring, $procedureNamespace = '', $entityNamesapce = '')
	{
		$this->instance = @pg_pconnect($connstring);

		if (false === $this->instance)
			throw new DatabaseConnectionException('Could not connect to database', 0);

		if ('\\' !== substr($entityNamesapce, -1))
			$entityNamesapce .= '\\';

		$this->nsEntities  = $entityNamesapce;
		$this->nsProcedure = $procedureNamespace;
	}

	public function __destruct()
	{
		pg_close($this->instance);
	}

	public function __call($name, $arguments)
	{
		$name = $this->nsProcedure . $name;
		array_unshift($arguments, $name);
		return call_user_func_array(array(&$this, 'storedProcedure'), $arguments);
	}

	public function getError()
	{
		return $this->instance->error . $this->instance->get_warnings();
	}

	public function begin()
	{
		pg_send_query($this->instance, 'BEGIN;');
		while (false !== pg_get_result($this->instance));
	}

	public function commit()
	{
		pg_send_query($this->instance, 'COMMIT;');
		while (false !== pg_get_result($this->instance));
	}

	public function rollback()
	{
		pg_send_query($this->instance, 'ROLLBACK;');
		while (false !== pg_get_result($this->instance));
	}

	public function storedProcedure($procedure, array $params = array(), $entityClass = null)
	{
		// Create the statement
		if (0 === count($params))
			$statement = sprintf('SELECT * FROM "%s"();', $procedure);
		else
		{
			try
			{
				array_walk($params, array(&$this, 'sanitize'));
			}
			catch (\Exception $ex)
			{
				throw new DatabaseException($ex->getMessage(), 0, 0, $procedure, $params, null);
			}
			$statement = sprintf('SELECT * FROM "%s"(%s);', $procedure, implode(', ', $params));
		}

		pg_send_query($this->instance, $statement);
		$result = pg_get_result($this->instance);

		if (4 < pg_result_status($result))
		{
			throw new DatabaseException(pg_result_error($result), pg_result_status($result, PGSQL_STATUS_STRING), pg_connection_status($this->instance), $procedure, $params, null);
		}

		if (false === empty($entityClass) && '\\' !== substr($entityClass, 0, 1))
			$entityClass = $this->nsEntities . $entityClass;

		return new Result($result, $entityClass);
	}

	private function sanitize(&$value, $key)
	{
		if (true === is_null($value))
		{
			$value = 'null';
		}
		else if (true === is_int($value))
		{
			return;
		}
		else if (true === is_bool($value))
		{
			$value = $value ? "'t'" : "'f'";
		}
		else if (true === is_string($value))
		{
			$value = sprintf('\'%s\'', pg_escape_string($this->instance, $value));
		}
		else if (true === is_array($value))
		{
			array_walk($value, array(&$this, __METHOD__));
			$value = sprintf('array [%s]', implode(', ', $value));
		}
		else
			throw new \Exception('Unable to bind param of type ' . ('object' === gettype($value) ? get_class($value) : gettype($value)) . ' at index ' . $key);
	}

	/**
	 * Empty static functinon for loading this file for use of the Entity
	 * classes when the database is not itself used.
	 */
	public static function init()
	{
	}
}

class Result implements \Countable, \Iterator, \ArrayAccess
{
	protected $result;
	/**
	 * @var int
	 */
	protected $rows;
	protected $class;

	protected $currentRow;
	protected $currentObject;

	public function __construct(&$result, $class = null)
	{
		$this->result = $result;
		$this->rows   = pg_num_rows($result);
		$this->class  = $class === null ? 'stdClass' : $class;
		$this->rewind();
	}

	public function count()
	{
		return $this->rows;
	}

	public function current()
	{
		return $this->currentObject;
	}

	public function key()
	{
		return $this->currentRow;
	}

	public function next()
	{
		++$this->currentRow;
		if ($this->currentRow < $this->rows)
			$this->currentObject = pg_fetch_object($this->result, $this->currentRow, $this->class);
		else
			$this->currentObject = null;
	}

	public function rewind()
	{
		$this->currentRow = -1;
		$this->next();
	}

	public function valid()
	{
		return (null !== $this->currentObject);
	}

	public function offsetExists($offset)
	{
		return (0 <= $offset && $offset < $this->rows);
	}

	public function offsetGet($offset)
	{
		return pg_fetch_object($this->result, $offset, $this->class);
	}

	public function offsetSet($offset, $value)
	{
		throw new \Exception('Database result in non-writeable');
		(int)$offset;(object)$value; // Unused variables
	}

	public function offsetUnset($offset)
	{
		throw new \Exception('Database result in non-writeable');
		(int)$offset; // unused variable
	}

	public function singleton()
	{
		if (1 === $this->rows)
			return $this[0];

		return null;
	}
}

class DatabaseException extends \Exception
{
	protected $procedure;
	protected $paramters;
	protected $sqlStatus;
	protected $qryStatus;

	public function __construct($message, $code, $state, $procedure, array $paramters, $previous)
	{
		parent::__construct($message, E_USER_ERROR, $previous);
		$this->procedure = $procedure;
		$this->paramters = $paramters;
		$this->sqlStatus = $state;
		$this->qryStatus = $code;
	}

	public function __toString()
	{
		return sprintf('[Procedure: %s; State: %s]%s%s%sParamters: %s',
			$this->procedure,
			$this->qryStatus,
			PHP_EOL,
			parent::getMessage(),
			PHP_EOL,
			print_r($this->paramters, true)
		);
	}
}

class DatabaseConnectionException extends DatabaseException
{
	public function __construct($message, $code)
	{
		parent::__construct($message, $code, 0, 'pg_connect', array(), null);
	}
}

abstract class Entity
{
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}
		throw new \Exception(sprintf('Property %s does not exist in Entity %s', $name, get_class($this)));
	}

	public function __isset($name)
	{
		return property_exists($this, $name);
	}

	public function __unset($name)
	{
		if (property_exists($this, $name))
			$this->$name = null;
	}
}

abstract class MutableEntity extends Entity
{
	public function __set($name, $value)
	{
		if (property_exists($this, $name))
			$this->$name = $value;
		else
			throw new \Exception(sprintf('Property %s does not exist in Entity %s', $name, get_class($this)));
	}
}
