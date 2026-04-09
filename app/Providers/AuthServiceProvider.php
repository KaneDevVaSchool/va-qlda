<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Project;
use App\Models\Vendor;
use App\Policies\ContractPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\VendorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Contract::class => ContractPolicy::class,
        Department::class => DepartmentPolicy::class,
        Vendor::class => VendorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
