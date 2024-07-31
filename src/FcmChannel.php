<?php

namespace Benwilkins\FCM;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Class FcmChannel.
 */
class FcmChannel
{
    private const API_URI = 'https://fcm.googleapis.com/fcm/send';

    private Client $client;

    private string $apiKey;

    public function __construct(Client $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function send($notifiable, Notification $notification): mixed
    {
        /** @var FcmMessage $message */
        $message = $notification->toFcm($notifiable);

        if (is_null($message->getTo()) && is_null($message->getCondition())) {
            if (! $to = $notifiable->routeNotificationFor('fcm', $notification)) {
                return [];
            }

            $message->to($to);
        }

        $responseArray = [];

        if (is_array($message->getTo())) {
            $chunks = array_chunk($message->getTo(), 1000);

            foreach ($chunks as $chunk) {
                $message->to($chunk);

                $responseArray[] = $this->sendPushNotification($notifiable, $notification, $message, $responseArray);
            }
        } else {
            $responseArray[] = $this->sendPushNotification($notifiable, $notification, $message, $responseArray);
        }

        return $responseArray;
    }

    private function sendPushNotification($notifiable, $notification, FcmMessage $message, array $responseArray): array
    {
        $response = $this->client->post(self::API_URI, [
            'headers' => [
                'Authorization' => 'key='.$this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body'    => $message->formatData(),
        ]);

        $responseArray = json_decode($response->getBody(), true);

        if ($response->getStatusCode() != 200) {
            $this->dispatchFailedNotification($notifiable, $notification, $responseArray);
        }

        return $responseArray;
    }

    protected function dispatchFailedNotification(mixed $notifiable, Notification $notification, array $report): void
    {
        NotificationFailedEvent::dispatch($notifiable, $notification, self::class, $report);
    }
}
