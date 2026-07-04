<?php

namespace App\Services;

use phpcent\Client;
use Throwable;

class Centrifugo
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.centrifugo.api_url'),
            config('services.centrifugo.api_key'),
            config('services.centrifugo.token_hmac_secret'),
        );
    }

    public static function boardChannel(int $projectId): string
    {
        return "project.{$projectId}.board";
    }

    /**
     * Недоступность реалтайма не должна ломать основное действие,
     * поэтому ошибки публикации только репортим.
     */
    public function publish(string $channel, array $data): void
    {
        try {
            $this->client->publish($channel, $data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function connectionToken(int $userId): string
    {
        return $this->client->generateConnectionToken((string) $userId, time() + 60 * 60 * 24);
    }
}
