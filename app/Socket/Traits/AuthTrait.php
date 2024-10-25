<?php

namespace App\Socket\Traits;

use App\Models\User;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Reverb\Contracts\Connection;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthTrait
{
    /**
     * Authenticate the user based on the reverb connection's cookie.
     *
     * @param  mixed  $connection  The connection object containing cookie data.
     * @return User|null The authenticated user or null if authentication fails.
     */
    protected function cookieAuth(Connection $connection): ?User
    {
        try {
            $token = $this->extractTokenFromConnection($connection);

            return $token ? $this->authenticateUserWithToken($token) : null;
        } catch (Exception $e) {
            Log::info('Parse error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extract the authentication token from the connection's cookie.
     *
     * @param  mixed  $connection  The connection object to extract the token from.
     * @return string|null The extracted token or null if not found.
     */
    private function extractTokenFromConnection($connection): ?string
    {
        // A closure to fetch the token from the connection.
        $fetcher = Closure::bind(function ($instance) {
            $http = $instance->connection->connection;
            // Get the HTTP buffer that contains the cookie data.
            $buffer = (fn () => $http->buffer)->call($http);

            if (Str::contains($buffer, 'Cookie: ')) {
                preg_match('/Cookie: (.+)/', $buffer, $matches);
                $cookieHeader = $matches[1] ?? null;
                parse_str(Str::replace('; ', '&', $cookieHeader), $res);

                return $res['auth_token'] ?? null;
            }

            return null;
        }, null, get_class($connection));

        return $fetcher($connection);
    }

    /**
     * Authenticate the user using the provided JWT token.
     *
     * @param  string  $token  The JWT token to authenticate the user.
     * @return User|null The authenticated user or null if authentication fails.
     */
    protected function authenticateUserWithToken(string $token): ?User
    {
        try {
            return JWTAuth::setToken(trim($token))->authenticate();
        } catch (TokenInvalidException $e) {
            Log::info('Invalid Token: '.$e->getMessage());
        } catch (TokenExpiredException $e) {
            Log::info('Token Expired: '.$e->getMessage());
        } catch (JWTException $e) {
            Log::info('Token parsing Error: '.$e->getMessage());
        }

        return null;
    }
}
