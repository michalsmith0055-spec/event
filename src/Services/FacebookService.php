<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;

final class FacebookService
{
    private Client $http;
    private string $graphVersion;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => 'https://graph.facebook.com/',
            'timeout' => 20,
        ]);
        $this->graphVersion = env('FACEBOOK_GRAPH_VERSION', 'v20.0');
    }

    public function getLoginUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => env('FACEBOOK_APP_ID'),
            'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
            'state' => $state,
            'scope' => 'pages_manage_metadata,pages_read_engagement,pages_manage_events',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?{$query}";
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = $this->http->get("{$this->graphVersion}/oauth/access_token", [
            'query' => [
                'client_id' => env('FACEBOOK_APP_ID'),
                'client_secret' => env('FACEBOOK_APP_SECRET'),
                'redirect_uri' => env('FACEBOOK_REDIRECT_URI'),
                'code' => $code,
            ],
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getPages(string $userAccessToken): array
    {
        $response = $this->http->get("{$this->graphVersion}/me/accounts", [
            'query' => ['access_token' => $userAccessToken],
        ]);

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        return $body['data'] ?? [];
    }

    public function createPageEvent(string $pageId, string $pageToken, array $event): array
    {
        $payload = $this->mapEventPayload($event);
        $payload['access_token'] = $pageToken;

        $response = $this->http->post("{$this->graphVersion}/{$pageId}/events", [
            'form_params' => $payload,
        ]);

        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function mapEventPayload(array $event): array
    {
        $start = new \DateTimeImmutable($event['start_date'] . ' ' . $event['start_time']);
        $end = new \DateTimeImmutable($event['end_date'] . ' ' . $event['end_time']);

        $payload = [
            'name' => $event['event_name'],
            'description' => $event['event_description'],
            'start_time' => $start->format(DATE_ATOM),
            'end_time' => $end->format(DATE_ATOM),
            'ticket_uri' => $event['ticket_url'],
            'event_category' => $event['category'] ?: 'MUSIC_EVENT',
        ];

        if (!empty($event['event_image_url'])) {
            $payload['cover_url'] = $event['event_image_url'];
        }

        if (strtolower($event['event_type']) === 'in person') {
            $payload['place'] = json_encode([
                'name' => $event['venue_name'],
                'location' => [
                    'city' => $event['city'],
                    'state' => $event['state'],
                    'country' => $event['country'],
                ],
            ], JSON_THROW_ON_ERROR);
        } else {
            $payload['online_event_format'] = 'fb_live';
        }

        return $payload;
    }
}
