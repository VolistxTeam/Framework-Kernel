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
    /**
     * Create a new personal token.
     *
     * @param array $inputs The input data for creating the personal token.
     *
     * @return Model|Builder The created personal token model or builder instance.
     */
    public function Create(array $inputs): Model|Builder
    {
        return PersonalToken::query()->create([
            'user_id'         => $inputs['user_id'],
            'name'            => $inputs['name'],
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
            'expires_at'      => $inputs['expires_at'],
            'hidden'          => $inputs['hidden'],
            'disable_logging' => $inputs['disable_logging'],
        ]);
    }

    /**
     * Update an existing personal token.
     *
     * @param string $userId  The ID of the user.
     * @param string $tokenId The ID of the personal token to update.
     * @param array  $inputs  The input data for updating the personal token.
     *
     * @return object|null The updated personal token object or null if token not found.
     */
    public function Update(string $userId, string $tokenId, array $inputs): ?object
    {
        $token = $this->Find($userId, $tokenId);
        if (!$token) {
            return null;
        }

        if (array_key_exists('name', $inputs)) {
            $token->name = $inputs['name'];
        }
        if (array_key_exists('rate_limit_mode', $inputs)) {
            $token->rate_limit_mode = $inputs['rate_limit_mode'];
        }
        if (array_key_exists('permissions', $inputs)) {
            $token->permissions = $inputs['permissions'];
        }
        if (array_key_exists('ip_rule', $inputs)) {
            $token->ip_rule = $inputs['ip_rule'];
        }
        if (array_key_exists('ip_range', $inputs)) {
            $token->ip_range = $inputs['ip_range'];
        }
        if (array_key_exists('country_rule', $inputs)) {
            $token->country_rule = $inputs['country_rule'];
        }
        if (array_key_exists('country_range', $inputs)) {
            $token->country_range = $inputs['country_range'];
        }
        if (array_key_exists('expires_at', $inputs)) {
            $token->expires_at = $inputs['expires_at'];
        }
        if (array_key_exists('disable_logging', $inputs)) {
            $token->disable_logging = $inputs['disable_logging'];
        }
        if (array_key_exists('hmac_token', $inputs)) {
            $token->hmac_token = $inputs['hmac_token'];
        }

        $token->save();

        return $token;
    }

    /**
     * Find a personal token by user ID and token ID.
     *
     * @param string $userId  The ID of the user.
     * @param string $tokenId The ID of the personal token to find.
     *
     * @return object|null The found personal token object or null if not found.
     */
    public function Find(string $userId, string $tokenId): ?object
    {
        return PersonalToken::query()
            ->where('id', $tokenId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Reset a personal token.
     *
     * @param string $userId  The ID of the user.
     * @param string $tokenId The ID of the personal token to reset.
     * @param array  $inputs  The input data for resetting the personal token.
     *
     * @return object|null The reset personal token object or null if token not found.
     */
    public function Reset(string $userId, string $tokenId, array $inputs): ?object
    {
        $token = $this->Find($userId, $tokenId);
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
     * Delete a personal token.
     *
     * @param string $userId  The ID of the user.
     * @param string $tokenId The ID of the personal token to delete.
     *
     * @return bool|null True if token deleted, null if token not found.
     */
    public function Delete(string $userId, string $tokenId): ?bool
    {
        $toBeDeletedToken = $this->Find($userId, $tokenId);
        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return true;
    }

    /**
     * Find all personal tokens with pagination support.
     *
     * @param string $search The search query.
     * @param int    $page   The page number.
     * @param int    $limit  The number of items per page.
     *
     * @return LengthAwarePaginator|null The paginated personal tokens or null if search query is invalid.
     */
    public function FindAll(string $search, int $page, int $limit): LengthAwarePaginator|null
    {
        // Handle empty search query
        if ($search === '') {
            $search = 'id:';
        }

        // Check if search query is in valid format
        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('personal_tokens');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        // Check if the column name is valid
        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return PersonalToken::query()
            ->where('hidden', false)
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Authenticate a personal token.
     *
     * @param string $token The personal token to authenticate.
     *
     * @return object|null The authenticated personal token object or null if authentication fails.
     */
    public function AuthPersonalToken(string $token): ?object
    {
        return PersonalToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }

    /**
     * Delete hidden tokens for a user.
     *
     * @param string $userId The ID of the user.
     *
     * @return bool True if hidden tokens deleted.
     */
    public function DeleteHiddenTokens(string $userId): bool
    {
        PersonalToken::query()->where('user_id', $userId)->where('hidden', true)->delete();

        return true;
    }
}
