# Changelog

All notable changes to `piggly/php-Capability-manager` will be documented in this file.

## 1.0.0 - `2021-05-08`

* First release. 

### `CapabilityOperations`

* Can check if `hasAll()` operations of all available and check if `hasInvalid()` operations comparing to available operations;

### `Capability`

* Added `fromArray()`, `fromJson()`, `insert()` and `disallowAny()` methods;
* Changed behavior to `add()`, `merge()` and `remove()` methods;
* Throwing exceptions when capabilities syntax or operation is invalid;

### `Capabilities`

* Removed `isHigher()` and `isLower()` since they don't achieve them purpose;
* Added `isFitting()` method to check if capabilities can fit to another;
* Added `hasCapability()` method if has a capability by a `Capability` object;
* Changed `isMatching()` behavior to strict matching two capabilities;
* `isAnyAllowed()` and `isAllAllowed()` methods will throw an exception if `$operators` were not set.