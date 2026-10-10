<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (User::where('email', 'dentist@dentivault.com')->doesntExist()) {
            $this->seed(DatabaseSeeder::class);
        }
    }
}
