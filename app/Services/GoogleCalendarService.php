<?php

namespace App\Services;

use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarService
{
    private function accessToken(Professional $professional): string
    {
        if (!$professional->google_refresh_token) {
            throw new RuntimeException('Google Calendar no está conectado.');
        }

        if ($professional->google_access_token &&
            $professional->google_token_expires_at?->isAfter(now()->addMinute())) {
            return $professional->google_access_token;
        }

        $response = Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
                'client_id' => config('services.google_calendar.client_id'),
                'client_secret' => config('services.google_calendar.client_secret'),
                'refresh_token' => $professional->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]
        )->throw()->json();

        $professional->google_access_token = $response['access_token'];
        $professional->google_token_expires_at =
            now()->addSeconds($response['expires_in'] ?? 3600);
        $professional->save();

        return $professional->google_access_token;
    }

    public function createEvent(
        Professional $professional,
        string $title,
        string $start,
        string $end
    ): string {
        $token = $this->accessToken($professional);

        $calendar = rawurlencode($professional->google_calendar_id ?: 'primary');

        $response = Http::withToken($token)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendar}/events", [
                'summary' => $title,
                'start' => [
                    'dateTime' => Carbon::parse($start, 'America/Bogota')->toRfc3339String(),
                    'timeZone' => 'America/Bogota',
                ],
                'end' => [
                    'dateTime' => Carbon::parse($end, 'America/Bogota')->toRfc3339String(),
                    'timeZone' => 'America/Bogota',
                ],
            ])->throw();

        return $response->json('id');
    }
}
