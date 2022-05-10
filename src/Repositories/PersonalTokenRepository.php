<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\PersonalToken;

class PersonalTokenRepository
{
    public function Create($subscription_id, array $inputs): Model|Builder
    {
        return PersonalToken::query()->create([
            'subscription_id' => $subscription_id,
            'key' => substr($inputs['key'], 0, 32),
            'secret' => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt' => $inputs['salt'],
            'permissions' => $inputs['permissions'],
            'ip_rule' => $inputs['ip_rule'],
            'ip_range' => $inputs['ip_range'],
            'country_rule' => $inputs['country_rule'],
            'country_range' => $inputs['country_range'],
            'activated_at' => Carbon::now(),
            'expires_at' => $inputs['duration'] != -1 ? Carbon::now()->addHours($inputs['duration']) : null,
            'hidden' => $inputs['hidden'],
        ]);
    }

    public function Update($subscription_id, $token_id, array $inputs): ?object
    {
        $token = $this->Find($subscription_id, $token_id);

        if (!$token) {
            return null;
        }

        $permissions = $inputs['permissions'] ?? null;
        $ip_rule = $inputs['ip_rule'] ?? null;
        $ip_range = $inputs['ip_range'] ?? null;
        $country_rule = $inputs['country_rule'] ?? null;
        $country_range = $inputs['country_range'] ?? null;
        $duration = $inputs['duration'] ?? null;

        if ($permissions !== null) {
            $token->permissions = $permissions;
        }

        if ($ip_rule !== null) {
            $token->ip_rule = $ip_rule;
        }

        if ($ip_range !== null) {
            $token->ip_range = $ip_range;
        }

        if ($country_rule !== null) {
            $token->country_rule = $country_rule;
        }

        if ($country_range !== null) {
            $token->country_range = $country_range;
        }

        if ($duration !== null) {
            $token->expires_at = $duration != -1 ? Carbon::createFromTimeString($token->activated_at)->addHours($duration) : null;
        }

        $token->save();

        return $token;
    }

    public function Find($subscription_id, $token_id): ?object
    {
        return PersonalToken::with('subscription')->where('id', $token_id)->where('subscription_id', $subscription_id)->first();
    }

    public function Reset($subscription_id, $token_id, array $inputs): ?object
    {
        $token = $this->Find($subscription_id, $token_id);

        if (!$token) {
            return null;
        }

        $token->key = substr($inputs['key'], 0, 32);
        $token->secret = SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]);
        $token->secret_salt = $inputs['salt'];
        $token->save();

        return $token;
    }

    public function Delete($subscription_id, $token_id): ?bool
    {
        $toBeDeletedToken = $this->Find($subscription_id, $token_id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return true;
    }

    public function FindAll($subscription_id, $needle, $page, $limit): LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('personal_tokens');

        return PersonalToken::query()->where('subscription_id', $subscription_id)->where('hidden', false)->where(function ($query) use ($columns, $needle) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$needle%");
            }
        })->paginate($limit, ['*'], 'page', $page);
    }

    public function AuthPersonalToken($token): ?object
    {
        return PersonalToken::with('subscription')->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }

    public function DeleteHiddenTokens($subscription_id): bool
    {
        PersonalToken::query()->where('subscription_id', $subscription_id)->where('hidden', true)->delete();

        return true;
    }
}
