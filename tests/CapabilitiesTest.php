<?php
namespace Piggly\Tests\CapabilitiesManager;

use PHPUnit\Framework\TestCase;
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

class CapabilitiesTest extends TestCase
{
	/**
	 * Capabilities.
	 * @var Capabilities
	 * @since 1.0.0
	 */
	protected $_caps;

	/**
	 * Setup base array for testing
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	protected function setUp () : void
	{ 
		$this->_caps = new Capabilities(); 

		$this->_caps->add(new Capability('manage_options:read,write'));
		$this->_caps->add(new Capability('posts:read,write,delete'));
		$this->_caps->add(new Capability('comments:read'));
	}

	/** @test Convert Capabilities object to an array. */
	public function capsToArray ()
	{ 
		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'manage_options' => [ 'read', 'write' ],
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ],
			] 
		); 
	}

	/** @test Convert Capability object to a string. */
	public function capsToString ()
	{ $this->assertSame( (string)$this->_caps, 'manage_options:read,write posts:read,write,delete comments:read' ); }

	/** @test Convert Capability object to a string. */
	public function capsToJson ()
	{ $this->assertSame( $this->_caps->toJson(), '{"manage_options":["read","write"],"posts":["read","write","delete"],"comments":["read"]}' ); }

	/** @test Test the serialize/unserialize behavior. */
	public function serializable ()
	{ 
		$serialized   = \serialize($this->_caps);
		$unserialized = \unserialize($serialized);

		$this->assertSame( $this->_caps->toArray(), $unserialized->toArray() ); 
	}

	/** @test Create a Capability object from a string. */
	public function capsFromString ()
	{ 
		$cap = new Capabilities('manage_options:read,write posts:read,write,delete comments:read');
		$this->assertSame( $this->_caps->toArray(), $cap->toArray() ); 
	}

	/** @test Get only Capabilities keys. */
	public function getPermsKeys ()
	{ $this->assertSame( $this->_caps->getKeys(), ['manage_options', 'posts', 'comments'] ); }

	/** @test Check if an operation is allowed to Capability. */
	public function assertOperationIsAllowed ()
	{ $this->assertTrue( $this->_caps->isAllowed('manage_options', 'read') ); }

	/** @test Check if an operation is not allowed to Capability. */
	public function assertOperationIsNotAllowed ()
	{ $this->assertFalse( $this->_caps->isAllowed('comments', 'write') ); }

	/** @test Check if a not found Capability is not allowed. */
	public function assertNotFoundCapabilityIsNotAllowed ()
	{ $this->assertFalse( $this->_caps->isAllowed('pages', 'write') ); }

	/** @test Check if any requested operation is allowed to Capability. */
	public function assertOperationIsAnyAllowed ()
	{ $this->assertTrue( $this->_caps->isAnyAllowed('manage_options', ['read','delete']) ); }

	/** @test Check if all requested operation is allowed to Capability. */
	public function assertOperationIsAllAllowed ()
	{ $this->assertTrue( $this->_caps->isAllAllowed('manage_options', ['read','write']) ); }

	/** @test Check if all requests operation is not allowed to Capability. */
	public function assertOperationIsNotAllAllowed ()
	{ $this->assertFalse( $this->_caps->isAllAllowed('manage_options', ['read','delete']) ); }

	/** @test Check if I can get a Capability. */
	public function assertGettingExpectedPerm ()
	{ $this->assertEquals( $this->_caps->get('posts')->getKey(), 'posts' ); }

	/** @test Check if I cannot get a not found Capability. */
	public function assertNotGettingNotFoundCapability ()
	{ $this->assertNull( $this->_caps->get('pages') ); }

	/** @test Check if I can iterate thought Capabilities. */
	public function assertIteration ()
	{ 
		$caps = [];

		foreach ( $this->_caps as $cap )
		{ $caps[] = $cap; }

		$this->assertSame( $this->_caps->getAll(), $caps ); 
	}

	/** @test Add a new Capability object. */
	public function addCapabilityObject ()
	{ 
		$this->_caps->add(new Capability('pages'));

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'manage_options' => [ 'read', 'write' ],
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ],
				'pages' => [ 'any' ]
			] 
		); 
	}

	/** @test Add a new Capability as string. */
	public function addCapabilitiestring ()
	{ 
		$this->_caps->add(new Capability('pages:read,write,delete'));

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'manage_options' => [ 'read', 'write' ],
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ],
				'pages' => [ 'read', 'write', 'delete' ]
			] 
		); 
	}

	/** @test Merge two Capabilities object. */
	public function mergeTwoCapabilities ()
	{ 
		$caps = new Capabilities('pages:read,write,delete stories:read');
		$this->_caps->merge($caps);

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'manage_options' => [ 'read', 'write' ],
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ],
				'pages' => [ 'read', 'write', 'delete' ],
				'stories' => [ 'read' ]
			] 
		); 
	}

	/** @test Assert if both Capabilities object is matching, having same Capabilities and operations. */
	public function assertBothIsMatching ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages'));

		$this->_caps->add(new Capability('pages'));

		$this->assertTrue( $this->_caps->isMatching($caps) ); 
	}

	/** @test Assert if both Capabilities object is not matching. */
	public function assertBothIsNotMatching ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages:read'));

		$this->_caps->add(new Capability('pages'));
		$this->assertFalse( $this->_caps->isMatching($caps) ); 
	}

	/** @test Assert if both Capabilities object is not matching. */
	public function assertBothIsNotMatchingByOperations ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages'));

		$this->_caps->add(new Capability('pages:read'));
		$this->assertFalse( $this->_caps->isMatching($caps) ); 
	}

	/** @test Assert if $caps is lower than $this->_caps with less Capabilities. */
	public function assertIsLowerWithLessCapability ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'));

		$this->assertTrue( $this->_caps->isLower($caps) ); 
	}

	/** @test Assert if $caps is not lower than $this->_caps with more Capabilities. */
	public function assertIsNotLowerWithMoreCapability ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages'));

		$this->assertFalse( $this->_caps->isLower($caps) ); 
	}

	/** @test Assert if $caps is lower than $this->_caps when $this->_caps has any operation. */
	public function assertIsLowerWithAnyOperation ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages:read'));

		$this->_caps->add(new Capability('pages'));
		$this->assertTrue( $this->_caps->isLower($caps) ); 
	}

	/** @test Assert if $caps is higher than $this->_caps when it has any operation. */
	public function assertIsNotLowerWithAnyOperation ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read'))
			->add(new Capability('pages'));

		$this->_caps->add(new Capability('pages:read'));
		$this->assertFalse( $this->_caps->isLower($caps) ); 
	}

	/** @test Assert if $caps is lower than $this->_caps with less operations. */
	public function assertIsNotLowerWithLessOperations ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write'))
			->add(new Capability('comments:read'));

		$this->assertTrue( $this->_caps->isLower($caps) ); 
	}

	/** @test Assert if $caps is not lower than $this->_caps with more operations. */
	public function assertIsNotLowerWithMoreOperations ()
	{ 
		$caps = new Capabilities(); 

		$caps
			->add(new Capability('manage_options:read,write'))
			->add(new Capability('posts:read,write,delete'))
			->add(new Capability('comments:read,write'));

		$this->assertFalse( $this->_caps->isLower($caps) ); 
	}

	/** @test Remove a Capability by a Capability object. */
	public function removeCapabilityByObject ()
	{
		$manage_options = $this->_caps->get('manage_options');
		$this->_caps->remove($manage_options);

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ]
			] 
		); 
	}

	/** @test Remove a Capability by a Capability object. */
	public function removeCapabilityByKey ()
	{
		$this->_caps->remove('manage_options');

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'posts' => [ 'read', 'write', 'delete' ],
				'comments' => [ 'read' ]
			] 
		); 
	}

	/** @test Remove many Capabilities. */
	public function removeManyCapabilities ()
	{
		$caps = new Capabilities();

		// It should remove any operations 
		// from manage_options and comments.
		$caps
			->add((new Capability())->setKey('manage_options'))
			->add((new Capability())->setKey('comments'));

		$this->_caps->removeMany($caps);

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'posts' => [ 'read', 'write', 'delete' ]
			] 
		); 
	}

	/** @test Remove a Capability by a Capability object. */
	public function removeManyPartialCapabilities ()
	{
		$caps = new Capabilities();

		// It should remove any operations from comments
		// and only write operation from manage_options.
		$caps
			->add((new Capability())->setKey('manage_options')->add('write'))
			->add((new Capability())->setKey('comments'));

		$this->_caps->removeMany($caps);

		$this->assertSame( 
			$this->_caps->toArray(), [ 
				'manage_options' => [ 'read' ],
				'posts' => [ 'read', 'write', 'delete' ]
			] 
		); 
	}
}