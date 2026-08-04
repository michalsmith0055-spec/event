<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConfigurationException;
use App\Exceptions\FacebookApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

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
            'client_id' => $this->requireConfig('FACEBOOK_APP_ID'),
            'redirect_uri' => $this->requireConfig('FACEBOOK_REDIRECT_URI'),
            'state' => $state,
            'scope' => 'pages_manage_metadata,pages_read_engagement,pages_manage_events',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?{$query}";
    }

    public function exchangeCodeForToken(string $code): array
    {
        $body = $this->request('GET', "{$this->graphVersion}/oauth/access_token", [
            'query' => [
                'client_id' => $this->requireConfig('FACEBOOK_APP_ID'),
                'client_secret' => $this->requireConfig('FACEBOOK_APP_SECRET'),
                'redirect_uri' => $this->requireConfig('FACEBOOK_REDIRECT_URI'),
                'code' => $code,
            ],
        ]);

        if (!isset($body['access_token']) || !is_string($body['access_token']) || $body['access_token'] === '') {
            throw new FacebookApiException('Facebook did not return an access token.');
        }

        return $body;
    }

    public function getPages(string $userAccessToken): array
    {
        $body = $this->request('GET', "{$this->graphVersion}/me/accounts", [
            'query' => ['access_token' => $userAccessToken],
        ]);

        $pages = $body['data'] ?? null;
        if (!is_array($pages)) {
            throw new FacebookApiException('Facebook returned an unexpected page list response.');
        }

        return $pages;
    }

    public function createPageEvent(string $pageId, string $pageToken, array $event): array
    {
        $payload = $this->mapEventPayload($event);
        $payload['access_token'] = $pageToken;

        $body = $this->request('POST', "{$this->graphVersion}/{$pageId}/events", [
            'form_params' => $payload,
        ]);

        if (!isset($body['id']) || !is_scalar($body['id'])) {
            throw new FacebookApiException('Facebook accepted the request but returned no event id.');
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $options): array
    {
        try {
            $response = $this->http->request($method, $uri, $options);
        } catch (BadResponseException $e) {
            throw new FacebookApiException($this->describeError($e->getResponse()), 0, $e);
        } catch (GuzzleException $e) {
            throw new FacebookApiException('Could not reach the Facebook Graph API: ' . $e->getMessage(), 0, $e);
        }

        return $this->decode($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $raw = (string) $response->getBody();

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new FacebookApiException('Facebook returned a malformed response: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new FacebookApiException('Facebook returned an unexpected response payload.');
        }

        if (isset($decoded['error'])) {
            throw new FacebookApiException($this->formatGraphError($decoded['error']));
        }

        return $decoded;
    }

    private function describeError(?ResponseInterface $response): string
    {
        if ($response === null) {
            return 'Facebook returned an error response with no body.';
        }

        $decoded = json_decode((string) $response->getBody(), true);
        if (is_array($decoded) && isset($decoded['error'])) {
            return $this->formatGraphError($decoded['error']);
        }

        return sprintf('Facebook returned HTTP %d.', $response->getStatusCode());
    }

    private function formatGraphError(mixed $error): string
    {
        if (!is_array($error)) {
            return 'Facebook returned an error: ' . (is_scalar($error) ? (string) $error : 'unknown error');
        }

        $message = (string) ($error['message'] ?? 'Unknown Facebook Graph API error.');
        $code = $error['code'] ?? null;
        $subCode = $error['error_subcode'] ?? null;

        return sprintf(
            'Facebook Graph API error%s: %s',
            $code === null ? '' : sprintf(' (code %s%s)', (string) $code, $subCode === null ? '' : '/' . (string) $subCode),
            $message
        );
    }

    private function requireConfig(string $key): string
    {
        $value = env($key);
        if ($value === null || trim($value) === '') {
            throw new ConfigurationException("Missing required configuration value: {$key}.");
        }

        return $value;
    }

    private function mapEventPayload(array $event): array
    {
        $start = $this->parseDateTime($event['start_date'] ?? '', $event['start_time'] ?? '', 'start');
        $end = $this->parseDateTime($event['end_date'] ?? '', $event['end_time'] ?? '', 'end');

        if ($end < $start) {
            throw new FacebookApiException('Event end time is before its start time.');
        }

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

        if (strtolower((string) $event['event_type']) === 'in person') {
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

    private function parseDateTime(mixed $date, mixed $time, string $label): \DateTimeImmutable
    {
        $value = trim((string) $date . ' ' . (string) $time);

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            throw new FacebookApiException("Invalid {$label} date/time \"{$value}\".", 0, $e);
        }
    }
}
