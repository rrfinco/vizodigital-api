<?php

namespace App\Providers;

use App\Repositories\Contracts\ApiEndpointRepositoryInterface;
use App\Repositories\Contracts\DocumentationRepositoryInterface;
use App\Repositories\Contracts\EnvironmentRepositoryInterface;
use App\Repositories\Contracts\NavigationRepositoryInterface;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Repositories\Eloquent\ApiEndpointRepository;
use App\Repositories\Eloquent\DocumentationRepository;
use App\Repositories\Eloquent\EnvironmentRepository;
use App\Repositories\Eloquent\NavigationRepository;
use App\Repositories\Eloquent\SearchRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ApiEndpointRepositoryInterface::class => ApiEndpointRepository::class,
        DocumentationRepositoryInterface::class => DocumentationRepository::class,
        EnvironmentRepositoryInterface::class => EnvironmentRepository::class,
        NavigationRepositoryInterface::class => NavigationRepository::class,
        SearchRepositoryInterface::class => SearchRepository::class,
    ];
}
