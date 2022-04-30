<?php

namespace Volistx\FrameworkKernel\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Helpers\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;

class AccessTokenRepository
{
    public function Create($subscription_id, array $inputs): Model|Builder
    {
        return AccessToken::query()->create([
            'key'           => substr($inputs['key'], 0, 32),
            'secret'        => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt'   => $inputs['salt'],
            'permissions'   => $inputs['permissions'],
            'ip_rule'       => $inputs['ip_rule'] ?? AccessRule::NONE,
            'ip_range'      => $inputs['ip_range'] ?? [],
            'country_rule'  => $inputs['country_rule'] ?? AccessRule::NONE,
            'country_range' => $inputs['country_range'] ?? [],
        ]);
    }

    public function Update($token_id, array $inputs): ?object
    {
        $token = $this->Find($token_id);

        if (!$token) {
            return null;
        }

        $permissions = $inputs['permissions'] ?? null;
        $ip_rule = $inputs['ip_rule'] ?? null;
        $ip_range = $inputs['ip_range'] ?? null;
        $country_rule = $inputs['country_rule'] ?? null;
        $country_range = $inputs['country_range'] ?? null;

        if (!$permissions && $ip_rule === null && !$ip_range && $country_rule === null && !$country_range) {
            return $token;
        }

        if ($permissions) {
            $token->permissions = $permissions;
        }

        if ($ip_rule !== null) {
            $token->ip_rule = $ip_rule;
        }

        if ($ip_range) {
            $token->ip_range = $ip_range;
        }

        if ($country_rule !== null) {
            $token->country_rule = $country_rule;
        }

        if ($country_range) {
            $token->country_range = $country_range;
        }

        $token->save();

        return $token;
    }

    public function Find($token_id): object|null
    {
        return AccessToken::query()->where('id', $token_id)->first();
    }

    public function Reset($token_id, $inputs): ?object
    {
        $token = $this->Find($token_id);

        if (!$token) {
            return null;
        }

        $token->key = substr($inputs['key'], 0, 32);
        $token->secret = SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]);
        $token->secret_salt = $inputs['salt'];
        $token->save();

        return $token;
    }

    public function Delete($token_id): ?bool
    {
        $toBeDeletedToken = $this->Find($token_id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return true;
    }

    public function FindAll($needle, $page, $limit): LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('access_tokens');
        $query = AccessToken::query();

        foreach ($columns as $column) {
            $query->orWhere("$column", 'LIKE', "%$needle%");
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    public function AuthAccessToken($token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
