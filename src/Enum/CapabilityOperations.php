<?php
namespace Piggly\CapabilitiesManager\Enum;

class CapabilityOperations
{
	/**
	 * All available operations.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	protected static $operations = [
		'read',
		'write',
		'delete',
		'destroy'
	];

	/**
	 * Replace all operations.
	 *
	 * @param array $operations
	 * @since 1.0.0
	 * @return void
	 */
	public static function set ( array $operations )
	{ self::$operations = $operations; }

	/**
	 * Add a new operation.
	 *
	 * @param string $operation
	 * @since 1.0.0
	 * @return void
	 */
	public static function add ( string $operation )
	{ 
		foreach ( self::$operations as $_operation )
		{
			if ( $_operation === $operation )
			{ return; }
		}

		self::$operations[] = $_operation;
	}

	/**
	 * Remove an operation.
	 *
	 * @param string $operation
	 * @since 1.0.0
	 * @return void
	 */
	public static function remove ( string $operation )
	{ 
		self::$operations = array_filter(
			self::$operations,
			function ( $_operation ) use ( $operation ) {
				return $_operation !== $operation;
			}
		);
	}

	/**
	 * Get all available operations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function operations () : array
	{ return self::$operations; }
}