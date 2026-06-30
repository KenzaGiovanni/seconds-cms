<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the base TestCase to the Feature and Unit suites so Pest tests get
| the full Laravel application bootstrapped.
|
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');
