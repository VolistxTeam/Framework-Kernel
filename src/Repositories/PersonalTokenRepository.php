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
    public function Create(array $inputs): Model|Builder
    {
        return PersonalToken::query()->create([
            'user_id'         => $inputs['user_id'],
            'key'             => substr($inputs['key'], 0, 32),
            'secret'          => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt'     => $inputs['salt'],
            'permissions'     => $inputs['permissions'],
            'rate_limit_mode' => $inputs['rate_limit_mode'],
            'ip_rule'         => $inputs['ip_rule'],
            'ip_range'        => $inputs['ip_range'],
            'country_rule'    => $inputs['country_rule'],
            'country_range'   => $inputs['country_range'],
            'hmac_token'      => $inputs['hmac_token'],
            'activated_at'    => Carbon::now(),
            'expires_at'      => $inputs['duration'] != null ? Carbon::now()->addHours($inputs['duration']) : null,
            'hidden'          => $inputs['hidden'],
            'disable_logging' => $inputs['disable_logging'],
        ]);
    }

    public function Update($token_id, array $inputs): ?object
    {
        $token = $this->Find($token_id);

        if (!$token) {
            return null;
        }

        $rate_limit_mode = $inputs['rate_limit_mode'] ?? null;
        $permissions = $inputs['permissions'] ?? null;
        $ip_rule = $inputs['ip_rule'] ?? null;
        $ip_range = $inputs['ip_range'] ?? null;
        $country_rule = $inputs['country_rule'] ?? null;
        $country_range = $inputs['country_range'] ?? null;
        $duration = $inputs['duration'] ?? null;
        $disable_logging = $inputs['disable_logging'] ?? null;
        $hmac_token = $inputs['hmac_token'] ?? null;

        if ($rate_limit_mode !== null) {
            $token->rate_limit_mode = $rate_limit_mode;
        }

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
            $token->expires_at = Carbon::createFromTimeString($token->activated_at)->addHours($duration);
        }

        if ($disable_logging !== null) {
            $token->disable_logging = $disable_logging;
        }

        if ($hmac_token !== null) {
            $token->hmac_token = $hmac_token;
        }

        $token->save();

        return $token;
    }

    public function Find($token_id): ?object
    {
        return PersonalToken::query()->where('id', $token_id)->first();
    }

    public function Reset($token_id, array $inputs): ?object
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

    public function FindAll($search, $page, $limit): LengthAwarePaginator|null
    {
        //handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('personal_tokens');

        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return PersonalToken::query()
            ->where('hidden', false)
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function AuthPersonalToken($token): ?object
    {
        return PersonalToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }

    public function DeleteHiddenTokens($user_id): bool
    {
        PersonalToken::query()->where('user_id', $user_id)->where('hidden', true)->delete();

        return true;
    }
}
