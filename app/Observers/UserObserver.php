<?php

namespace App\Observers;

use App\Events\NewUserEvent;
use App\Models\Company;
use App\Models\Notification;
use App\Models\TicketAgentGroups;
use App\Models\User;
use App\Traits\StoreHeaders;
use App\Models\UserAuth;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;

class UserObserver
{

    use StoreHeaders;

    public function saving(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($user->isDirty('status') && $user->status == 'deactive') {
                // Remove as ticket agent
                TicketAgentGroups::whereAgentId($user->id)->delete();
            }
            
            // Check if activating user would exceed package limit
            if ($user->isDirty('status') && $user->status == 'active' && $user->getOriginal('status') != 'active') {
                $company = Company::find($user->company_id);
                if ($company && $company->package) {
                    $currentActiveCount = User::where('company_id', $user->company_id)
                        ->where('status', 'active')
                        ->whereHas('employeeDetail')
                        ->count();
                        
                    if ($currentActiveCount >= $company->package->max_employees) {
                        throw new \Exception(__('superadmin.maxEmployeesLimitReached'));
                    }
                }
            }
        }

        session()->forget('user');
        clearCompanyValidPackageCache($user->company_id);
    }

    public function created(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $sendMail = true;

            if (request()->has('sendMail') && request()->sendMail == 'no') {
                $sendMail = false;
            }

            if ($sendMail && auth()->check() && request()->email != '') {
                event(new NewUserEvent($user, session('auth_pass')));
            }

            session()->forget('auth_pass');
        }
    }

    public function creating(User $model)
    {
        if (company()) {
            $model->company_id = company()->id;
        }

        $this->storeHeaders($model);

    }

    public function deleting(User $user)
    {
        Notification::where('type', 'App\Notifications\NewUser')
            ->whereNull('read_at')
            ->where(function ($q) use ($user) {
                $q->where('data', 'like', '{"id":' . $user->id . ',%');
            })->delete();
    }

    public function deleted(User $user)
    {
        $userCount = User::withoutGlobalScopes([CompanyScope::class, ActiveScope::class])->where('user_auth_id', $user->user_auth_id)->count();

        // If deleted user has no other account then delete it from user_auth table also
        if ($userCount == 0) {
            UserAuth::destroy($user->user_auth_id);
        }

        clearCompanyValidPackageCache($user->company_id);
    }

}
