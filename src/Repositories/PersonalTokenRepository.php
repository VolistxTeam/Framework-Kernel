<?php

namespace Volistx\FrameworkKernel\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Classes\SHA256Hasher;
use Volistx\FrameworkKernel\Models\PersonalToken;

class PersonalTokenRepository
{
    public function Create($subscription_id, array $inputs): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return PersonalToken::query()->create([
            'subscription_id' => $subscription_id,
            'key'             => substr($inputs['key'], 0, 32),
            'secret'          => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt'     => $inputs['salt'],
            'permissions'     => $inputs['permissions'],
            'whitelist_range' => $inputs['whitelist_range'],
            'activated_at'    => Carbon::now(),
            'expires_at'      => $inputs['hours_to_expire'] != -1 ? Carbon::now()->addHours($inputs['hours_to_expire']) : null,
        ]);
    }

    public function Update($subscription_id, $token_id, array $inputs)
    {
        $token = $this->Find($subscription_id, $token_id);

        if (!$token) {
            return null;
        }

        $permissions = $inputs['permissions'] ?? null;
        $whitelistRange = $inputs['whitelist_range'] ?? null;
        $hours_to_expire = $inputs['hours_to_expire'] ?? null;

        if (!$permissions && !$whitelistRange && !$hours_to_expire) {
            return $token;
        }

        if ($permissions) {
            $token->permissions = $permissions;
        }

        if ($whitelistRange) {
            $token->whitelist_range = $whitelistRange;
        }

        if ($hours_to_expire) {
            $token->expires_at = $hours_to_expire != -1 ? Carbon::createFromTimeString($token->activated_at)->addHours($hours_to_expire) : null;
        }

        $token->save();

        return $token;
    }

    public function Find($subscription_id, $token_id): object|null
    {
        return PersonalToken::query()->where('id', $token_id)->where('subscription_id', $subscription_id)->first();
    }

    /**
     * @param (mixed|string)[] $inputs
     *
     * @psalm-param array{key: mixed, salt: string} $inputs
     */
    public function Reset($subscription_id, $token_id, array $inputs)
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

    /**
     * @return null|string[]
     *
     * @psalm-return array{result: 'true'}|null
     */
    public function Delete($subscription_id, $token_id): array|null
    {
        $toBeDeletedToken = $this->Find($subscription_id, $token_id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return [
            'result' => 'true',
        ];
    }

    public function FindAll($subscription_id, $needle, $page, $limit)
    {
        $columns = Schema::getColumnListing('personal_tokens');

        return PersonalToken::where('subscription_id', $subscription_id)->where(function ($query) use ($columns, $needle) {
            foreach ($columns as $column) {
                $query->orWhere("$column", 'LIKE', "%$needle%");
            }
        })->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * @param null|string $token
     */
    public function AuthPersonalToken(string|null $token)
    {
        return PersonalToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
