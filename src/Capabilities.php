<?php
namespace Piggly\CapabilitiesManager;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Serializable;

/**
 * The Capabilities object is a package of many Capability object.
 * By using it you can check all Capabilities and do some processing
 * to it.
 * 
 * @since 1.0.0 
 * @package Piggly\CapabilitiesManager
 * @subpackage Piggly\CapabilitiesManager
 * @author Caique <caique@piggly.com.br>
 */
class Capabilities implements Serializable, JsonSerializable, IteratorAggregate
{
	/**
	 * The string delimiter between each Capability.
	 * By default, a space.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const PERMS_DELIMITER = ' ';

	/**
	 * All Capability available.
	 *
	 * @var array<Capability>
	 * @since 1.0.0
	 */
	private $_caps;

	/**
	 * Create a new Capability manager.
	 * Capabilities are delimited by a space char.
	 *
	 * @param string $capabilities A string with all Capabilities.
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct ( string $capabilities = null )
	{
		$this->_caps = [];

		if ( empty($capabilities) )
		{ return; }

		$this->import($capabilities);
	}

	/**
	 * Import Capabilities to current Capabilities object.
	 *
	 * @param string $capabilities
	 * @param array $default_operators To apply if operations were not set to a Capability.
	 * @since 1.0.0
	 * @return void
	 */
	public function import ( string $capabilities, array $default_operators = null )
	{
		$capabilities = \explode( self::PERMS_DELIMITER, $capabilities );

		$this->_caps = \array_map(
			function ( $capability ) use ( $default_operators ) {
				return new Capability($capability, $default_operators);
			},
			$capabilities
		);
	}

	/**
	 * Import Capabilities from array. Following the schema:
	 * [ $key => $operations, $key => $operations ].
	 *
	 * @param array $caps
	 * @since 1.0.0
	 * @return void
	 */
	public function fromArray ( array $caps )
	{
		foreach ( $caps as $key => $operations )
		{ $this->_caps[] = (new Capability())->setKey($key)->set($operations); }

		return $this;
	}

	/**
	 * Import Capabilities from json. Following the schema:
	 * { "$key": [$operations], "$key": [$operations] }.
	 *
	 * @param string $json
	 * @since 1.0.0
	 * @return void
	 */
	public function fromJson ( string $json )
	{ return $this->fromArray(json_decode($json, true)); }

	/**
	 * Get all Capability keys.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function getKeys ()  : array
	{
		return \array_map(
			function ( $capability ) {
				return $capability->getKey();
			},
			$this->_caps
		);
	}

	/**
	 * Check if $this and $caps has the exactly same
	 * Capabilities and operations. All need to match for TRUE return.
	 * 
	 * @param Capabilities $caps
	 * @since 1.0.0
	 * @since 1.0.0 Should be exactly same.
	 * @return bool
	 */
	public function isMatching ( Capabilities $caps ) : bool
	{
		foreach  ( $caps as $_external_caps )
		{
			$_local_cap = $this->get($_external_caps->getKey());

			// $caps has a capability $this hasn't
			if ( empty($_local_cap) )
			{ return false; }

			// Cannot fit any operations if doesnt allow it
			if ( $_external_caps->isAnyAllowed() && !$_local_cap->isAnyAllowed() )
			{ return false; }

			foreach ( $_local_cap->get() as $operation )
			{
				if ( \in_array($operation, $_external_caps->get(), true) === false )
				{ return false; }
			}
		}

		return true;
	}

	/**
	 * Check if $this fits $caps capabilities.
	 * 
	 *
	 * @param Capabilities $caps
	 * @since 1.0.0
	 * @return boolean
	 */
	public function isFitting ( Capabilities $caps ) : bool
	{
		foreach  ( $caps as $_external_caps )
		{
			$_local_cap = $this->get($_external_caps->getKey());

			// $caps has a capability $this hasn't
			if ( empty($_local_cap) )
			{ return false; }

			// Cannot fit any operations if doesnt allow it
			if ( $_external_caps->isAnyAllowed() && !$_local_cap->isAnyAllowed() )
			{ return false; }
			
			// Cannot fit all $_external_caps operations
			if ( !$_local_cap->hasAll($_external_caps->get()) )
			{ return false; }
		}

		return true;
	}

	/**
	 * Check if has capability.
	 *
	 * @param Capability $capability
	 * @since 1.0.0
	 * @return boolean
	 */
	public function hasCapability ( Capability $capability )
	{
		$_cap = $this->get($capability->getKey());

		if ( empty($_cap) )
		{ return false; }

		return $_cap->hasAll($capability->get());
	}

	/**
	 * Check if Capability $key is allowed and if the
	 * requested $operation is allowed.
	 *
	 * @param Capability|string $key
	 * @param string $operation
	 * @since 1.0.0
	 * @since 1.0.0 $key as Capability or string
	 * @return boolean
	 */
	public function isAllowed ( $key, string $operation )
	{
		$_cap = $this->get($key instanceof Capability ? $key->getKey() : $key);

		if ( empty($_cap) )
		{ return false; }

		return $_cap->has($operation);
	}
	
	/**
	 * Check if Capability $key is allowed and if any
	 * requested $operations are allowed.
	 *
	 * @param Capability|string $key
	 * @param string|array ...$operations
	 * @since 1.0.0
	 * @since 1.0.0 $key as Capability or string
	 * @since 1.0.0 Throw an exception if $operations is empty
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function isAnyAllowed ( string $key, ...$operations )
	{
		$_cap = $this->get($key instanceof Capability ? $key->getKey() : $key);

		if ( empty($_cap) )
		{ return false; }

		if ( empty($operations) )
		{ throw new InvalidArgumentException('You should sent some $operations at method `isAnyAllowed()`.'); }

		return $_cap->hasAny($operations);
	}
	
	/**
	 * Check if Capability $key is allowed and if all
	 * requested $operations are allowed.
	 *
	 * @param Capability|string $key
	 * @param string ...$operations
	 * @since 1.0.0
	 * @since 1.0.0 $key as Capability or string
	 * @since 1.0.0 Throw an exception if $operations is empty
	 * @return boolean
	 * @throws InvalidArgumentException
	 */
	public function isAllAllowed ( string $key, ...$operations )
	{
		$_cap = $this->get($key instanceof Capability ? $key->getKey() : $key);

		if ( empty($_cap) )
		{ return false; }

		if ( empty($operations) )
		{ throw new InvalidArgumentException('You should sent some $operations at method `isAllAllowed()`.'); }

		return $_cap->hasAll($operations);
	}

	/**
	 * Get a Capability object by key.
	 *
	 * @param Capability|string $key Capability key or object.
	 * @since 1.0.0
	 * @return Capability|null
	 */
	public function get ( $key ) : ?Capability
	{
		$key = $key instanceof Capability ? $key->getKey() : $key;

		/** @var Capability $capability */
		foreach ( $this->_caps as $capability )
		{
			if ( $capability->isKey( $key ) )
			{ return $capability; }
		}

		return null;
	}

	/**
	 * Get all Capability objects.
	 *
	 * @since 1.0.0
	 * @return array<Capability>
	 */
	public function getAll () : array
	{ return $this->_caps; }

	/**
	 * Add a new Capability object.
	 *
	 * @param Capability|string $cap
	 * @since 1.0.0
	 * @return self
	 */
	public function add ( $cap )
	{ $this->_caps[] = $cap instanceof Capability ? $cap : new Capability($cap); return $this; }

	/**
	 * Merge with a new Capabilities object. All
	 * new Capabilities that already exists in current
	 * _caps array will merge the operations.
	 *
	 * @param Capabilities $caps
	 * @since 1.0.0
	 * @return self
	 */
	public function merge ( Capabilities $caps )
	{ 
		foreach ( $caps as $cap )
		{
			$_cap = $this->get($cap->getKey());

			if ( empty($_cap) )
			{ $this->add($cap); continue; }

			$_cap->merge($cap->get());
		}

		return $this;
	}

	/**
	 * Remove a Capability object by key.
	 *
	 * @param string $key
	 * @since 1.0.0
	 * @return self
	 */
	public function remove ( $key )
	{
		$key = $key instanceof Capability ? $key->getKey() : $key;

		$this->_caps = \array_filter(
			$this->_caps,
			function ( $cap ) use ( $key ) {
				return !$cap->isKey($key);
			}
		);

		return $this;
	}

	/**
	 * Remove one or more Capabilities from a Capabilities
	 * object.
	 * 
	 * The behavior here is based on $caps Capabilities,
	 * if at $caps:
	 * 
	 * 	There is a Capability with any operations,
	 * 	it will completely remove the Capability.
	 * 
	 * 	If not, it will remove any operations set in
	 * 	$caps Capability from $this->_caps Capability.
	 *
	 * @param Capabilities $caps
	 * @since 1.0.0
	 * @return self
	 */
	public function removeMany ( Capabilities $caps )
	{
		foreach ( $caps as $cap )
		{
			$_cap = $this->get($cap->getKey());

			if ( !empty($_cap) )
			{
				if ( $cap->isAnyAllowed() )
				{ $this->remove($cap); continue; }

				$_cap->remove($cap->get());

				if ( empty($_cap->get()) )
				{ $this->remove($cap); }
			}
		}
	}

	/**
	 * Remove all Capabilities.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function removeAll ()
	{ $this->_caps = []; return $this; }

	/**
	 * Retrieve an external iterator.
	 *
	 * @since 1.0.0
	 * @return ArrayIterator
	 */
	public function getIterator ()
	{ return new ArrayIterator($this->_caps); }
	
	/**
	 * Export Capability data to an array.
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function toArray () : array
	{
		$caps = [];

		foreach ( $this->_caps as $cap )
		{ $caps[$cap->getKey()] = $cap->get(); }

		return $caps;
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
	public function jsonSerialize ()
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
		
		if ( is_array($data) )
		{ $this->fromArray($data); }
	} 

	/**
	 * Convert the Payload to its string representation.
	 *
	 * @since 1.0.4
	 * @return string
	 */
	public function __toString ()
	{ 
		$caps = \array_filter(
			$this->_caps,
			function ( $cap ) {
				return (string)$cap;
			}
		);

		return \implode(self::PERMS_DELIMITER, $caps); 
	}
}