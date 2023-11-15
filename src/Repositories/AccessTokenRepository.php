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
    /**
     * Create a new access token.
     *
     * @param array $inputs The input data for creating the access token.
     * @return Model|Builder The created access token model or builder instance.
     */
    public function Create(array $inputs): Model|Builder
    {
        return AccessToken::query()->create([
            'key' => substr($inputs['key'], 0, 32),
            'secret' => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt' => $inputs['salt'],
            'permissions' => $inputs['permissions'],
            'ip_rule' => $inputs['ip_rule'] ?? AccessRule::NONE,
            'ip_range' => $inputs['ip_range'] ?? [],
            'country_rule' => $inputs['country_rule'] ?? AccessRule::NONE,
            'country_range' => $inputs['country_range'] ?? [],
        ]);
    }

    /**
     * Update an existing access token.
     *
     * @param string $tokenId The ID of the access token to update.
     * @param array $inputs The input data for updating the access token.
     * @return object|null The updated access token object or null if token not found.
     */
    public function Update(string $tokenId, array $inputs): ?object
    {
        $token = $this->Find($tokenId);
        if (!$token) {
            return null;
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

        $token->save();
        return $token;
    }

    /**
     * Find an access token by ID.
     *
     * @param string $tokenId The ID of the access token to find.
     * @return object|null The found access token object or null if not found.
     */
    public function Find(string $tokenId): object|null
    {
        return AccessToken::query()->where('id', $tokenId)->first();
    }

    /**
     * Reset an access token.
     *
     * @param string $tokenId The ID of the access token to reset.
     * @param array $inputs The input data for resetting the access token.
     * @return object|null The reset access token object or null if token not found.
     */
    public function Reset(string $tokenId, $inputs): ?object
    {
        $token = $this->Find($tokenId);
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
     * Delete an access token.
     *
     * @param string $tokenId The ID of the access token to delete.
     * @return bool|null True if token deleted, null if token not found.
     */
    public function Delete(string $tokenId): ?bool
    {
        $toBeDeletedToken = $this->Find($tokenId);
        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();
        return true;
    }

    /**
     * Find all access tokens with pagination support.
     *
     * @param string $search The search query.
     * @param int $page The page number.
     * @param int $limit The number of items per page.
     * @return LengthAwarePaginator|null The paginated access tokens or null if search query is invalid.
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

        $columns = Schema::getColumnListing('access_tokens');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        // Check if the column name is valid
        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return AccessToken::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * Authenticate an access token.
     *
     * @param string $token The access token to authenticate.
     * @return object|null The authenticated access token object or null if authentication fails.
     */
    public function AuthAccessToken(string $token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}