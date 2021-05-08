<?php
namespace Piggly\CapabilitiesManager;

use InvalidArgumentException;
use JsonSerializable;
use Piggly\CapabilitiesManager\Enum\CapabilityOperations;
use RuntimeException;
use Serializable;

/**
 * A Capability object retains a key meaning the Capability key
 * required and an operations array, containing keyword
 * to operations processes, such as: read, write, delete, destroy, any.
 *  
 * @since 1.0.0 
 * @package Piggly\CapabilitiesManager
 * @subpackage Piggly\CapabilitiesManager
 * @author Caique <caique@piggly.com.br>
 */
class Capability implements Serializable, JsonSerializable
{
	/**
	 * Key.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $_key;

	/**
	 * Operations.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $_operations;

	/**
	 * Create a new Capability.
	 *
	 * @param string $capability
	 * @param array $default_operators To apply if operations were not set.
	 * @since 1.0.0
	 * @since 1.0.1 Default operations or empty array to $this->_operations
	 * @return void
	 */
	public function __construct ( string $capability = null, array $default_operators = null )
	{
		$this->_operations = $default_operators ?? [];

		if ( empty($capability) )
		{ return; }

		$this->import($capability, $default_operators);
	}

	/**
	 * Import a Capability to current Capability object.
	 *
	 * @param string $capability
	 * @param array $default_operators To apply if operations were not set.
	 * @since 1.0.0
	 * @return void
	 */
	public function import ( string $capability, array $default_operators = null )
	{
		$capabilities = implode('|', CapabilityOperations::operations() );
		\preg_match(
			'/^(?<key>[^\:]+)(?:\:(?<operators>(?:(?:'.$capabilities.')\,?)+))?$/i',
			$capability,
			$_blocks,
			\PREG_UNMATCHED_AS_NULL
		);

		if ( !isset($_blocks['key']) || empty($_blocks['key']) )
		{ 
			throw new InvalidArgumentException(
				\sprintf(
					'Invalid syntax found `%s`. Make sure it uses any of available capabilities: `%s`.', 
					$capability,
					implode('`, `', CapabilityOperations::operations() )
				)
			); 
		}

		$this->_key = $_blocks['key'];

		if ( empty($_blocks['operators']) && !empty($default_operators) )
		{
			$this->_operations = $default_operators;
			return $this;
		}

		if ( empty($_blocks['operators']) )
		{ 
			$this->_operations = ['any'];
			return $this; 
		}

		$_blocks['operators'] = \explode(',', $_blocks['operators']);

		// Has all available operations, so adding any instead
		if ( CapabilityOperations::hasAll($_blocks['operators']) )
		{ 
			$this->_operations = ['any'];
			return $this; 
		}

		$this->_operations = $_blocks['operators'];
		return $this;
	}

	/**
	 * Import Capability from array. Following the schema:
	 * [ $key => $operations ].
	 *
	 * @param array $caps
	 * @since 1.0.0
	 * @return void
	 */
	public static function fromArray ( array $caps ) : Capability
	{
		foreach ( $caps as $key => $operations )
		{ return (new Capability())->setKey($key)->set($operations); }

		return null;
	}

	/**
	 * Import Capability from json.
	 * { "$key": [$operations] }.
	 *
	 * @param string $json
	 * @since 1.0.1
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public static function fromJson ( string $json ) : Capability
	{ 
		$json = json_decode($json, true);

		if ( JSON_ERROR_NONE !== \json_last_error() ) 
		{ throw new InvalidArgumentException(\sprintf('Cannot parse Capability object from JSON: `%s`.', \json_last_error_msg())); }

		$cap = (new Capability())->setKey(\array_key_first($json));
		return $cap->set($json[$cap->getKey()]);
	}

	/**
	 * Set Capability key.
	 *
	 * @param string $key
	 * @since 1.0.0
	 * @return self
	 */
	public function setKey ( string $key )
	{ $this->_key = $key; return $this; }

	/**
	 * Get Capability key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function getKey () : ?string
	{ return $this->_key ?? null; }

	/**
	 * Check if $expected is equal to current key.
	 *
	 * @param string $expected
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isKey ( string $expected )
	{ return $this->_key === $expected; }

	/**
	 * Set Capability operations.
	 *
	 * @param array $operations
	 * @since 1.0.0
	 * @return self
	 */
	public function set ( $operations )
	{ $this->_operations = $operations; return $this; }

	/**
	 * Get all operations.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public function get () : array
	{ return $this->_operations; }

	/**
	 * Add a new operation.
	 * 
	 * If current Capability allow "any" operation,
	 * will thrown an exception to use `disallowAny`
	 * method first.
	 *
	 * @param string|array ...$operations
	 * @since 1.0.0
	 * @since 1.0.1 Thrown an exception if cannot add because capability allow any.
	 * @return self
	 * @throws InvalidArgumentException If cannot add because capability allow any.
	 */
	public function add ( ...$operations )
	{ 
		if ( $this->isAnyAllowed() )
		{ throw new InvalidArgumentException('The capability allows `any` operations. You should use `disallowAny()` first to adding new operations.'); }

		$this->merge($operations);
		return $this; 
	}

	/**
	 * Insert a new operation.
	 * 
	 * If current Capability allow "any" operation,
	 * flushes operations by removing it an continue
	 * adding.
	 *
	 * @param string|array ...$operations
	 * @since 1.0.0
	 * @since 1.0.1 Thrown an exception if cannot add because capability allow any.
	 * @return self
	 */
	public function insert ( ...$operations )
	{ 
		if ( $this->isAnyAllowed() )
		{ $this->_operations = []; }

		$this->merge($operations);
		return $this; 
	}

	/**
	 * Merge operations.
	 *
	 * @param array $operations
	 * @since 1.0.0
	 * @since 1.0.1 Add "any" if $this->_operations has all operations available.
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function merge ( array $operations )
	{ 
		$this->_operations = \array_unique( \array_merge( $this->_operations, $this->_fixSpread($operations) ) );
		
		// If has all available operations, change it to any
		if ( CapabilityOperations::hasAll($this->_operations) )
		{ $this->_operations = ['any']; }
		
		return $this; 
	}

	/**
	 * Remove one or more operation.
	 * 
	 * @param string $operations
	 * @since 1.0.0
	 * @since 1.0.1 If any is allowed, add all available operations removing $operations.
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function remove ( ...$operations )
	{
		$operations = $this->_fixSpread($operations);

		// If any is allowed, then get all operations allowed
		// and remove required $operations from it
		if ( $this->isAnyAllowed() )
		{ 
			$this->_operations = \array_diff(CapabilityOperations::operations(), $operations); 
			return $this;
		}

		$this->_operations = \array_diff($this->_operations, $operations);
		return $this;
	}

	/**
	 * Check if $expected operation make part of this Capability.
	 *
	 * @param string $expected
	 * @since 1.0.0
	 * @return bool
	 */
	public function has ( string $expected ) : bool
	{ 
		if ( $this->isAnyAllowed() )
		{ return true; }

		return \in_array($expected, $this->_operations, true) !== false; 
	}

	/**
	 * Check if this Capability has at least one of $expected operations.
	 *
	 * @param string|array ...$expected
	 * @since 1.0.0
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function hasAny ( ...$expected )
	{ 
		if ( $this->isAnyAllowed() )
		{ return true; }

		return !empty(\array_intersect($this->_fixSpread($expected), $this->_operations)); 
	}

	/**
	 * Check if this Capability has all $expected operations.
	 *
	 * @param string|array ...$expected
	 * @since 1.0.0
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function hasAll ( ...$expected )
	{
		if ( $this->isAnyAllowed() )
		{ return true; }

		$expected = $this->_fixSpread($expected);
		return \count(\array_intersect($expected, $this->_operations)) === \count($expected); 
	}

	/**
	 * Allow any operations.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Return $this
	 * @return self
	 */
	public function allowAny ()
	{ $this->_operations = ['any']; return $this; }

	/**
	 * Disallow any operation.
	 *
	 * @since 1.0.1
	 * @return self
	 */
	public function disallowAny ()
	{ $this->_operations = []; return $this; }

	/**
	 * Check if allow any operation.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function isAnyAllowed () : bool
	{ return \in_array('any', $this->_operations, true) !== false; }

	/**
	 * Fix spread operation by validating its data and
	 * formatting.
	 *
	 * @param array $spread
	 * @since 1.0.0
	 * @return array
	 * @throws InvalidArgumentException
	 */
	private function _fixSpread ( $spread ) : array
	{
		$_spread = [];

		foreach ( $spread as $item )
		{
			if ( is_array($item) )
			{ 
				$_spread = \array_merge($_spread, $this->_fixSpread($item)); 
				continue;
			}
			
			if ( !is_string($item) )
			{ 
				throw new InvalidArgumentException(
					\sprintf(
						'Operation term `%s` should be a string or an array.', 
						\gettype($item)
					)
				); 
			}

			$_spread[] = $item;
		}

		$_spread = \array_unique($_spread);

		if ( CapabilityOperations::hasInvalid($_spread) )
		{ 
			throw new InvalidArgumentException(
				\sprintf(
					'Invalid operation term `%s` found. Should be any of: %s.', 
					\gettype($item), 
					implode('`, `', CapabilityOperations::operations())
				)
			); 
		}

		return $_spread;
	}

	/**
	 * Export Capability data to an array.
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function toArray () : array
	{
		return [
			$this->_key => $this->_operations ?? []
		];
	}

	/**
	 * Export all payload data to a JSON string.
	 * 
	 * @since 1.0.0
	 * @return string
	 * @throws RuntimeException If can't parse JSON.
	 */
	public function toJson ( int $option = \JSON_ERROR_NONE, int $depth = 512 ) : string
	{
		$json = \json_encode( $this->jsonSerialize(), $option, $depth );

		if ( JSON_ERROR_NONE !== \json_last_error() ) 
		{ throw new RuntimeException(\sprintf('Cannot parse Capability object to JSON: `%s`.', \json_last_error_msg())); }

		return $json;
	}
 
	/**
	 * Prepare the resource for JSON serialization.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function jsonSerialize()
	{ return $this->toArray(); }

	/**
	 * Generate a storable representation of payload object.
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function serialize ()
	{ return \serialize($this->toArray()); }

	/**
	 * Create a Payload object from a stored representation.
	 * 
	 * @param string $data
	 * @since 1.0.0
	 * @return string
	 */
	public function unserialize ( $data )
	{
		$data = \unserialize($data); 

		$this->_key = \array_key_first($data);
		$this->_operations = $data[$this->_key];
	} 

	/**
	 * Convert the Payload to its string representation.
	 *
	 * @since 1.0.4
	 * @return string
	 */
	public function __toString ()
	{ return \sprintf('%s:%s', $this->_key, \implode(',', $this->_operations)); }
}