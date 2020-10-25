<?php


namespace App\Contracts;


interface OAuthServiceContract
{
    public function auth(string $code): array;

    public function getAuthenticatedUser(string $token): array;
}
