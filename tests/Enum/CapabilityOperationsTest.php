<?php
namespace Piggly\Tests\CapabilitiesManager\Enum;

use PHPUnit\Framework\TestCase;
use Piggly\CapabilitiesManager\Enum\CapabilityOperations;

class CapabilityOperationsTest extends TestCase
{
	/** @test Assert has all available operations. */
	public function assertArrayHasAllOperations ()
	{ 
		$this->assertTrue( 
			CapabilityOperations::hasAll([
				'read',
				'write',
				'delete',
				'destroy'
			])
		); 
	}

	/** @test Assert has all available operations without order. */
	public function assertArrayHasAllOperationsMixed ()
	{ 
		$this->assertTrue( 
			CapabilityOperations::hasAll([
				'delete',
				'write',
				'read',
				'destroy'
			])
		); 
	}

	/** @test Assert has invalid operations. */
	public function assertArrayHasInvalidOperations ()
	{ 
		$this->assertTrue( 
			CapabilityOperations::hasInvalid([
				'delete',
				'write',
				'read',
				'unknown'
			])
		); 
	}

	/** @test Assert hasn't all available operations. */
	public function assertArrayHasNotAllOperations ()
	{ 
		$this->assertFalse( 
			CapabilityOperations::hasAll([
				'delete',
				'write'
			])
		); 
	}
	
	/** @test Assert hasn't invalid operations. */
	public function assertArrayHasNotInvalidOperations ()
	{ 
		$this->assertFalse( 
			CapabilityOperations::hasInvalid([
				'delete',
				'write'
			])
		); 
	}
}