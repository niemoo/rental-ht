<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Roles and permissions are created by a migration; make sure no stale cache hides them.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
