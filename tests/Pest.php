<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case bindings
|--------------------------------------------------------------------------
|
| Bind the application TestCase to the Feature and Unit suites so Pest tests
| boot the Laravel container. Database-backed tests opt into RefreshDatabase
| explicitly with `uses(RefreshDatabase::class)` at the top of the file.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit');
