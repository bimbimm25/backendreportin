<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();


$SECRET_KEY_JWT = getenv('SECRET_KEY_JWT');


if (!$SECRET_KEY_JWT) {
    $SECRET_KEY_JWT = $_ENV['SECRET_KEY_JWT'] ?? null;
}
?>