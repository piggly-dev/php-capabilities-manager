<?php
namespace Piggly\Tests\CapabilitiesManager;

use PHPUnit\Framework\TestCase;
use Piggly\CapabilitiesManager\Capability;

class CapabilityTest extends TestCase
{
	/**
	 * Capability.
	 * @var Capability
	 * @since 1.0.0
	 */
	protected $_cap;

	/**
	 * Setup base array for testing
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	protected function setUp () : void
	{
		$this->_cap = (new Capability())->setKey('manage_options')->set(['read', 'write']);
	}

	/** @test Convert Capability object to an array. */
	public function capToArray ()
	{ $this->assertSame( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write' ] ] ); }

	/** @test Convert Capability object to a string. */
	public function capToString ()
	{ $this->assertSame( (string)$this->_cap, 'manage_options:read,write' ); }

	/** @test Convert Capability object to a string. */
	public function capToJson ()
	{ $this->assertSame( $this->_cap->toJson(), '{"manage_options":["read","write"]}' ); }

	/** @test Test the serialize/unserialize behavior. */
	public function serializable ()
	{ 
		$serialized   = \serialize($this->_cap);
		$unserialized = \unserialize($serialized);

		$this->assertSame( $this->_cap->toArray(), $unserialized->toArray() ); 
	}

	/** @test Create a Capability object from a string. */
	public function capFromString ()
	{ 
		$cap = new Capability('manage_options:read,write');
		$this->assertSame( $this->_cap->toArray(), $cap->toArray() ); 
	}

	/** @test Create a Capability object with no operations from a string. */
	public function capFromStringWithNoOperation ()
	{ 
		$cap = new Capability('manage_options');
		$this->assertSame( ['any'], $cap->get() ); 
	}

	/** @test Create a Capability object with default operations from a string. */
	public function capFromStringWithDefaultOperations ()
	{ 
		$cap = new Capability('manage_options', ['delete']);
		$this->assertSame( ['delete'], $cap->get() ); 
	}

	/** @test Add a new operation to Capability. */
	public function addOperation ()
	{ 
		$this->_cap->add('delete');
		$this->assertSame( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete' ] ] );
	}

	/** @test Add many operations to Capability. */
	public function addOperations ()
	{ 
		$this->_cap->add('delete','destroy');
		$this->assertSame( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete', 'destroy' ] ] );
	}

	/** @test Add mixed operations to Capability. */
	public function addMixedOperations ()
	{ 
		$cap = new Capability('manage_options');
		$cap->add('read',['delete','destroy']);
		$this->assertEqualsCanonicalizing( $cap->toArray(), [ 'manage_options' => [ 'read', 'delete', 'destroy' ] ] );
	}

	/** @test Add an operation which already exists. */
	public function addExistingOperation ()
	{ 
		$this->_cap->add('read');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write' ] ] );
	}

	/** @test Add mixed operations with some already exists. */
	public function addMixedExistingOperation ()
	{ 
		$this->_cap->add(['read'],'write',['delete','destroy']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete', 'destroy' ] ] );
	}

	/** @test Create a Capability with which allows "any" operation and add another operation, removing "any". */
	public function removeAnyOperationByAddingOperation ()
	{ 
		$cap = new Capability('manage_options');
		$cap->add('read');
		$this->assertEqualsCanonicalizing( ['read'], $cap->get() ); 
	}

	/** @test Merge operations. */
	public function mergingOperations ()
	{ 
		$this->_cap->merge(['read','delete','destroy']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete', 'destroy' ] ] );
	}

	/** @test Remove an operation. */
	public function removeOperation ()
	{ 
		$this->_cap->remove('read');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'write' ] ] );
	}

	/** @test Remove many operations. */
	public function removeOperations ()
	{ 
		$this->_cap->remove('read', 'write');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [] ] );
	}

	/** @test Remove mixed operations. */
	public function removeMixedOperations ()
	{ 
		$this->_cap->add('delete')->remove('read', ['write']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'delete' ] ] );
	}

	/** @test If Capability object has a operation. */
	public function hasOperation ()
	{ $this->assertTrue($this->_cap->has('read')); }

	/** @test If Capability object has a operation when allow any. */
	public function hasOperationWhenAllowAny ()
	{ 
		$cap = new Capability('manage_options');
		$this->assertTrue($cap->has('read')); 
	}

	/** @test If Capability object has any requested operation. */
	public function hasAnyOperation ()
	{ $this->assertTrue($this->_cap->hasAny(['read','delete'])); }

	/** @test If Capability object has all requested operation. */
	public function hasAllOperation ()
	{ $this->assertTrue($this->_cap->hasAll(['read','write'])); }

	/** @test If Capability object hasn't a operation. */
	public function hasnotOperation ()
	{ $this->assertFalse($this->_cap->has('delete')); }

	/** @test If Capability object hasn't all requested operation. */
	public function hasnotAllOperation ()
	{ $this->assertFalse($this->_cap->hasAll('read','delete')); }
}