<?php
namespace Piggly\Tests\CapabilitiesManager;

use InvalidArgumentException;
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
	{ $this->_cap = (new Capability())->setKey('manage_options')->set(['read', 'write']); }

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

	/** @test Create a Capability object from a string. */
	public function capFromJson ()
	{ 
		$cap = Capability::fromJson('{"manage_options":["read","write"]}');
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

	/** @test Create a Capability object with default operations from a string. */
	public function throwAnExceptionIfInvalidSyntax ()
	{ 
		$this->expectException( InvalidArgumentException::class );
		$cap = new Capability('manage_options:unknown');
	}

	/** @test Assert if change to any when creating with all operations. */
	public function assertIfChangeToAnyWhenCreatingAllOperations ()
	{ 
		$cap = new Capability('manage_options:read,write,delete,destroy');
		$this->assertSame( $cap->toArray(), [ 'manage_options' => [ 'any' ] ] );
	}

	/** @test Assert if is any allowed when creating with all operations. */
	public function assertIfIsAnyAllowedWhenCreatingAllOperations ()
	{ 
		$cap = new Capability('manage_options:read,write,delete,destroy');
		$this->assertTrue( $cap->isAnyAllowed() );
	}

	/** @test Add a new operation to Capability. */
	public function addOperation ()
	{ 
		$this->_cap->add('delete');
		$this->assertSame( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete' ] ] );
	}

	/** @test Add many operations to Capability. */
	public function assertIfWhenAddingAllOperationsChangeToAny ()
	{ 
		$this->_cap->add('delete','destroy');
		$this->assertSame( $this->_cap->toArray(), [ 'manage_options' => [ 'any' ] ] );
	}

	/** @test Throw an exception when trying to adding if capability allow any. */
	public function throwAnExceptionIfTryingToAddWhenAllowAny ()
	{ 
		$this->expectException( InvalidArgumentException::class );
		$this->_cap->add('delete','destroy')->add('read');
	}

	/** @test Throw an exception when trying to adding if capability allow any. */
	public function throwAnExceptionWhenTryingToAddInvalidOperation ()
	{ 
		$this->expectException( InvalidArgumentException::class );
		$this->_cap->add('unknown');
	}

	/** @test Add mixed operations to Capability. */
	public function addMixedOperations ()
	{ 
		$cap = (new Capability())->setKey('manage_options');
		$cap->add('read',['delete','destroy']);
		$this->assertSame( $cap->toArray(), [ 'manage_options' => [ 'read', 'delete', 'destroy' ] ] );
	}

	/** @test Ignore when adding an operation which already exists. */
	public function IgnoreWhenAddingAnExistingOperation ()
	{ 
		$this->_cap->add('read');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write' ] ] );
	}

	/** @test Add mixed operations with some already exists. */
	public function addMixedExistingOperations ()
	{ 
		$this->_cap->add(['read'],'write',['delete','destroy']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'any' ] ] );
	}

	/** @test Create a Capability with which allows "any" operation and add another operation, removing "any". */
	public function removeAnyOperationByAddingOperation ()
	{ 
		$cap = new Capability('manage_options');
		$cap->insert('read');
		$this->assertEqualsCanonicalizing( ['read'], $cap->get() ); 
	}

	/** @test Merge operations. */
	public function mergingOperations ()
	{ 
		$this->_cap->merge(['read','delete']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'read', 'write', 'delete' ] ] );
	}

	/** @test Remove an operation. */
	public function removeOperation ()
	{ 
		$this->_cap->remove('read');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'write' ] ] );
	}

	/** @test Remove an operation when allowing any operations. */
	public function removeOperationWhenAllowingAny ()
	{ 
		$cap = new Capability('manage_options');
		$cap->remove('read');
		$this->assertEqualsCanonicalizing( $cap->toArray(), [ 'manage_options' => [ 'write', 'delete', 'destroy' ] ] );
	}

	/** @test Remove many operations. */
	public function removeOperations ()
	{ 
		$this->_cap->remove('read', 'write');
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [] ] );
	}

	/** @test Remove operations when allowing any operations. */
	public function removeOperationsWhenAllowingAny ()
	{ 
		$cap = new Capability('manage_options');
		$cap->remove('read','delete');
		$this->assertEqualsCanonicalizing( $cap->toArray(), [ 'manage_options' => [ 'write', 'destroy' ] ] );
	}

	/** @test Remove mixed operations. */
	public function removeMixedOperations ()
	{ 
		$this->_cap->add('delete')->remove('read', ['write']);
		$this->assertEqualsCanonicalizing( $this->_cap->toArray(), [ 'manage_options' => [ 'delete' ] ] );
	}

	/** @test If Capability object has a operation. */
	public function assertItHasOperation ()
	{ $this->assertTrue($this->_cap->has('read')); }

	/** @test If Capability object hasn't a operation. */
	public function assertItHasNotOperation ()
	{ $this->assertFalse($this->_cap->has('delete')); }

	/** @test If Capability object has a operation when allow any. */
	public function assertItHasOperationWhenAllowAny ()
	{ 
		$cap = new Capability('manage_options');
		$this->assertTrue($cap->has('read')); 
	}

	/** @test If Capability object has any of requested operations. */
	public function assertItHasAnyOfRequestedOperations ()
	{ $this->assertTrue($this->_cap->hasAny(['read','delete'])); }

	/** @test If Capability object has any of requested operations. */
	public function assertItHasWithLessRequiredOperations ()
	{ $this->assertTrue($this->_cap->hasAny(['read'])); }

	/** @test If Capability object hasn't any of requested operations. */
	public function assertItHasNotAnyOfRequestedOperations ()
	{ $this->assertFalse($this->_cap->hasAny(['delete','destroy'])); }

	/** @test If Capability object hasn't any of requested operations. */
	public function assertItHasNotAnyWithLessRequiredOperations ()
	{ $this->assertFalse($this->_cap->hasAny(['delete'])); }

	/** @test If Capability object has all of requested operations. */
	public function assertItHasAllOfRequestedOperations ()
	{ $this->assertTrue($this->_cap->hasAll(['read','write'])); }

	/** @test If Capability object hasn't all of requested operations. */
	public function assertItHasNotAllOfRequestedOperations ()
	{ $this->assertFalse($this->_cap->hasAll(['read','delete'])); }

	/** @test If Capability object hasn't all of requested operation. */
	public function assertItHasNotAllWithLessRequiredOperations ()
	{ $this->assertFalse($this->_cap->hasAll(['delete'])); }
}