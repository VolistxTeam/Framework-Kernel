<?php

namespace Volistx\FrameworkKernel\Repositories;

use Illuminate\Support\Facades\Schema;
use Volistx\FrameworkKernel\Classes\SHA256Hasher;
use Volistx\FrameworkKernel\Models\AccessToken;

class AccessTokenRepository
{
    public function Create($subscription_id, array $inputs): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
    {
        return AccessToken::query()->create([
            'key'             => substr($inputs['key'], 0, 32),
            'secret'          => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt'     => $inputs['salt'],
            'permissions'     => $inputs['permissions'],
            'whitelist_range' => $inputs['whitelist_range'],
        ]);
    }

    public function Update($token_id, array $inputs)
    {
        $token = $this->Find($token_id);

        if (!$token) {
            return null;
        }

        $permissions = $inputs['permissions'] ?? null;
        $whitelistRange = $inputs['whitelist_range'] ?? null;

        if (!$permissions && !$whitelistRange) {
            return $token;
        }

        if ($permissions) {
            $token->permissions = json_encode($permissions);
        }

        if ($whitelistRange) {
            $token->whitelist_range = json_encode($whitelistRange);
        }

        $token->save();

        return $token;
    }

    public function Find($token_id): object|null
    {
        return AccessToken::query()->where('id', $token_id)->first();
    }

    public function Reset($token_id, $inputs)
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

    /**
     * @return null|string[]
     *
     * @psalm-return array{result: 'true'}|null
     */
    public function Delete($token_id): array|null
    {
        $toBeDeletedToken = $this->Find($token_id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return [
            'result' => 'true',
        ];
    }

    public function FindAll($needle, $page, $limit): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $columns = Schema::getColumnListing('access_tokens');
        $query = AccessToken::query();

        foreach ($columns as $column) {
            $query->orWhere("$column", 'LIKE', "%$needle%");
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * @param array|null|string|true $token
     */
    public function AuthAccessToken(array|bool|string|null $token)
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
