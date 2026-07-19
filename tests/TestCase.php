<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (app()->environment() !== 'testing' || $connection !== 'mysql' || $database !== 'avatech_smart_pmis_testing') {
            throw new RuntimeException('Tests may write only to the MySQL avatech_smart_pmis_testing database.');
        }
    }
}
