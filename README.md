Hack Error Suppressor [![Build Status](https://travis-ci.org/fredemmott/hack-error-suppressor.svg?branch=master)](https://travis-ci.org/fredemmott/hack-error-suppressor)
=====================

Unless the `hhvm.hack.lang.look_for_typechecker` ini setting is set to false,
by default HHVM will try to run the typechecker when loading any Hack files,
and raise a catchable runtime fatal error.

This is a PHP library that makes it convenient to temporarily disable this
behavior.

When This Is Useful
-------------------

HHVM's behavior is problematic when:

 - HHVM can't work out where the project root is - for example, if you're trying
   to run Hack code from a composer plugin
 - If your Hack code needs to operate on an incomplete project - for example, if
   you wish to write Hack code to fetch dependencies
 - If your Hack code needs to operate on known-bad projects - for example,
   when updating generated code, the code may be inconsistent while your
   codegen is in process

When You Shouldn't Use This
---------------------------

It's probably not appropriate if any of these are true:
 - it's used outside of a build/install or codegen process
 - it's used when *using* generated code
 - it's called during normal use of your software, rather than just by
   developers
 - it's used outside of the CLI

Typechecker errors are real problems with code, and mean that things *are*
broken; ignoring enforcement is only a good idea if you are expecting your code
to be running on a temporarily-broken codebase, and your code fixes it.

Installation
------------

```
$ composer require fredemmott/hack-error-suppressor
```

Usage
-----

You must enable error suppression before any Hack code is loaded.

You can explicitly enable and disable the suppression:

```PHP
<?php
use FredEmmott\HackErrorSuppressor;

$it = new HackErrorSuppressor();
$it->enable();
call_some_hack_code();
$it->disable();
```

You can also enable the suppression with a scope:

```PHP
<?php

use FredEmmott\ScopedHackErrorSuppressor;

function do_unsafe_stuff() {
  $suppressor = new ScopedHackErrorSuppressor();
  call_some_hack_code(); // this works
}

do_unsafe_stuff();
call_some_hack_code(); // this fails, as we're out of scope
```

Using Outside Of The CLI
------------------------

This is disallowed by default; if you're absolutely certain you need to do this
(keeping in mind that typechecker errors are real problems with your code, not
just lint) you can disable the check:

```PHP
FredEmmott\HackErrorSuppressor::allowRealRequestsAgainstBrokenCode();
```
