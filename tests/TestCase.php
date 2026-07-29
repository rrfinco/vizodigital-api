<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->forceSqliteForTests();

        parent::setUp();
    }

    private function forceSqliteForTests(): void
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_URL');
        putenv('DB_HOST');
        putenv('DB_PORT');
        putenv('DB_USERNAME');
        putenv('DB_PASSWORD');

        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['DB_URL'] = '';
        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_URL'] = '';

        unset(
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD'],
            $_SERVER['DB_HOST'],
            $_SERVER['DB_PORT'],
            $_SERVER['DB_USERNAME'],
            $_SERVER['DB_PASSWORD'],
        );
    }
}
