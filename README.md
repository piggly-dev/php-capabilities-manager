# Capabilities Manager Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/piggly/php-capabilities-manager.svg?style=flat-square)](https://packagist.org/packages/piggly/php-capabilities-manager) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE) 

The **Capability Manager** is a library which allows better controlling and checking for Capabilities and allowed operations to each Capability.

## How?

It's a common practice to systems have some `scopes`, `roles` or even `permissions` and `capabilities` to Users or Clients. And often, they also attached to operations. This library assumes the following operations exists:

Operation | Description
--- | ---
read | Can only read resources.
write | Can create and edit resources.
delete | Can send a resource to trash.
destroy | Can permanently delete a resource.
any | Can do any of all operations available.

You can, of course, customize them by using`\Piggly\CapabilitiesManager\Enum\CapabilityOperations`class. Adding new operations with `add()` method, removing with `remove()` method and using `set()` method to a completely new `array` of operations.

After, these operations can be attached to capabilities. For example, see below some capabilities samples:

Capability | Description
--- | ---
manage_options | Can manages options.
posts | Can manages posts.
comments | Can manages comments.
pages | Can manages pages.

> You can, if want or your system need, just ignore operations behavior and use only capabilities keys. This library can support it.

If an user may be allowed to only read posts, his capabilities will be `posts:read`. We can achieve this in a smart and simple way:

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities();
$caps->add((new Capability())->setKey('posts')->add('read'));
```

What if the same user can also read/edit comments?

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities();
$caps
	->add((new Capability())->setKey('posts')->add('read'))
	->add((new Capability())->setKey('comments')->add('read', 'write'));
```

> Keep in mind `Capability` object does not care about care about operations order, which means `add('read', 'write')` is same as `add('write', 'read')`.

Somehow, the same user can do any operations at `page` capability. So:

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities();
$caps
	->add((new Capability())->setKey('posts')->add('read'))
	->add((new Capability())->setKey('comments')->add('read', 'write'))
	->add((new Capability())->setKey('pages')->allowAny();
```

### Capabitities Syntax

But, there also a simple and compact way to add/create capabilities. By using the capability syntax, which is: `<capability_key>:<operation>,...,<operation>`. See below:

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities();
$caps
	->add(new Capability('posts:read'))
	->add(new Capability('comments:read,write'))
	->add(new Capability('pages'));
```

Or even more compact by separating capabilities syntax with a space char:

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities('posts:read comments:read,write pages');
```

If you don't attach operations at syntax, such as `page` above, capability syntax will allow the `any` operation to that capability. If you want to change this behaviour, you will need to send some default operations.

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

$caps = new Capabilities('posts:read comments:read,write pages', ['read']);
```

Now `pages` will only have `read` operation.

> A `Capability` object will throw an `InvalidArgumentException` if something wrong with capability syntax. For example, if you create `comments:read,write,unknown`, an exception will be thrown because `unknown` is not a valid operation.

## And how it can help me?

After creating capabilities, you can a lot of things to them, such as:

* Check if one `Capabilities` object has the exactly same capabilities of another by using `isMatching()` method;
* Check if one `Capabilities` object has higher capabilities than another by using `isHigher()` method;
* Check if one `Capabilities` object has lower capabilities than another by using `isLower()` method;
* Check if a capability and operation is allowed at `Capabilities` object with `isAllowed()` method;
* Check if any of required operations for a capabitity is allowed at `Capabilities` object with `isAnyAllowed()` method;
* Check if all of required operations for a capability is allowed at `Capabilities` object with `isAllAllowed()` method;
* You can manage capabilities with `add()`, `merge()`, `remove()`, `removeMany()` and `get()` methods at `Capabilities` object;
* You can `serialize` the `Capabilities` object, convert to `json`, convert to `array` or even to `string`.

### Real-world minimal example

```php
use Piggly\CapabilitiesManager\Capability;
use Piggly\CapabilitiesManager\Capabilities;

// Getting sent capabitities
$caps = filter_input ( INPUT_POST, 'capabilities', FILTER_SANITIZE_STRING );

// Create capabilities
try
{ $caps = new Capabilities($caps); }
catch ( Exception $e )
{ return 'You capability syntax is invalid.'; }

// You can save this capabilities to user of many ways:

$user->setCapabilities($caps->toJson())->save(); // json format
$user->setCapabilities((string)$caps)->save(); // string format
$user->setCapabilities(serialize($caps))->save(); // serialized format

// ... soon, you can read accordingly
$caps = (new Capabilities())->fromJson($user->getCapabilities()); // json format
$caps = new Capabilities($user->getCapabilities()); // string format
$caps = unserialize($user->getCapabilities()); // unserialized format

// To better control user data, you have to do the User object manages Capabilities object:
// Class below is a simple sample
class User
{
	// ...
	public function setCapabilities ( Capabilities $caps )
	{ $this->caps = $caps; }

	public function getCapabilities () : Capabilities
	{ return $caps; }

	public function save ()
	{
		// ...
		$this->caps = (string) $caps;
		DB::save($this);
	}
	
	public function load ()
	{
		$data = DB::load($this);
		// ...
		$this->caps = new Capabilities($data['caps']);
	}
}

// ... then, user may try to access a middleware requiring some capability and operation
$required_capability = 'posts';
$required_operation = 'read';

if ( !$caps->isAllowed($required_capability, $required_operation) )
{ /** User cannot access it **/ }
```

`Capabilities` class is flexible and can achive many goals by managing users/clients capabitities at any kind of system.


## Changelog

See the [CHANGELOG](CHANGELOG.md) file for information about all code changes.

## Testing the code

This library uses the PHPUnit. We carry out tests of all the main classes of this application.

```bash
vendor/bin/phpunit
```

## Contributions

See the [CONTRIBUTING](CONTRIBUTING.md) file for information before submitting your contribution.

## Credits

- [Caique Araujo](https://github.com/caiquearaujo)
- [All contributors](../../contributors)

## Support the project

Piggly Studio is an agency located in Rio de Janeiro, Brazil. If you like this library and want to support this job, be free to donate any value to BTC wallet `3DNssbspq7dURaVQH6yBoYwW3PhsNs8dnK` ❤.

## License

MIT License (MIT). See [LICENSE](LICENSE).