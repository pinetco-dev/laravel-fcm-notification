# Laravel FCM Notification
Laravel FCM (Firebase Cloud Messaging) Notification Channel

[![GitHub tag](https://badgen.net/github/tag/benwilkins/laravel-fcm-notification)](https://github.com/benwilkins/laravel-fcm-notification/releases)
[![Packagist](https://badgen.net/packagist/v/benwilkins/laravel-fcm-notification)](https://packagist.org/packages/benwilkins/laravel-fcm-notification)
[![Downloads](https://badgen.net/packagist/dt/benwilkins/laravel-fcm-notification)](https://packagist.org/packages/benwilkins/laravel-fcm-notification)
[![Build Status](https://travis-ci.com/benwilkins/laravel-fcm-notification.svg)](https://travis-ci.com/benwilkins/laravel-fcm-notification)
[![License](https://badgen.net/packagist/license/benwilkins/laravel-fcm-notification)](https://packagist.org/packages/benwilkins/laravel-fcm-notification)

Use this package to send push notifications via Laravel to Firebase Cloud Messaging. Laravel 5.5+ required.

This package works only with [Legacy HTTP Server Protocol](https://firebase.google.com/docs/cloud-messaging/http-server-ref)

## Install

This package can be installed through Composer.

``` bash
composer require benwilkins/laravel-fcm-notification
```

If installing on < Laravel 5.5 then add the service provider:

```php
// config/app.php
'providers' => [
    ...
    Benwilkins\FCM\FcmNotificationServiceProvider::class,
    ...
];
```

Add your Firebase API Key in `config/services.php`.

```php
return [
   
    ...
    ...
    /*
     * Add the Firebase API key
     */
    'fcm' => [
        'key' => env('FCM_SECRET_KEY')
     ]
];
```

## Example Usage

Use Artisan to create a notification:

```bash
php artisan make:notification SomeNotification
```

Return `[fcm]` in the `public function via($notifiable)` method of your notification:

```php
public function via($notifiable)
{
    return ['fcm'];
}
```

Add the method `public function toFcm($notifiable)` to your notification, and return an instance of `FcmMessage`: 

```php
use Benwilkins\FCM\FcmMessage;

...

public function toFcm($notifiable) 
{
    $message = new FcmMessage();
    $message->content([
        'title'        => 'Foo', 
        'body'         => 'Bar', 
        'sound'        => '', // Optional 
        'icon'         => '', // Optional
        'click_action' => '' // Optional
    ])->data([
        'param1' => 'baz' // Optional
    ])->priority(FcmMessage::PRIORITY_HIGH); // Optional - Default is 'normal'.
    
    return $message;
}
```

When sending to specific device, make sure your notifiable entity has `routeNotificationForFcm` method defined: 

```php
/**
 * Route notifications for the FCM channel.
 *
 * @param  \Illuminate\Notifications\Notification  $notification
 * @return string
 */
public function routeNotificationForFcm($notification)
{
    return $this->device_token;
}
```

When sending to a topic, you may define so within the `toFcm` method in the notification:

```php
use Benwilkins\FCM\FcmMessage;

...

public function toFcm($notifiable) 
{
    $message = new FcmMessage();
    $message->to('the-topic', $recipientIsTopic = true)
    ->content([...])
    ->data([...]);
    
    return $message;
}
```

Or when sending with a condition:

```php
use Benwilkins\FCM\FcmMessage;

...

public function toFcm($notifiable) 
{
    $message = new FcmMessage();
    $message->contentAvailable(true)
        ->priority('high')
        ->condition("'user_".$notifiable->id."' in topics")
        ->data([...]);
    
    return $message;
}
```

You may provide optional headers or override the request headers using `setHeaders()`:

```php
use Benwilkins\FCM\FcmMessage;

...

public function toFcm($notifiable) 
{
    $message = new FcmMessage();
    $message->setHeaders([
        'project_id'    =>  "48542497347"   // FCM sender_id
    ])->content([
        'title'        => 'Foo', 
        'body'         => 'Bar', 
        'sound'        => '', // Optional 
        'icon'         => '', // Optional
        'click_action' => '' // Optional
    ])->data([
        'param1' => 'baz' // Optional
    ])->priority(FcmMessage::PRIORITY_HIGH); // Optional - Default is 'normal'.
    
    return $message;
}
```

## Handling errors

When a notification fails it will dispatch an `Illuminate\Notifications\Events\NotificationFailed` event. You can listen
for this event and choose to handle these notifications as appropriate. For example, you may choose to delete expired
notification tokens from your database.

```php
<?php

namespace App\Listeners;

use Benwilkins\FCM\FcmChannel;
use Benwilkins\FCM\NotificationFailedEvent;

class DeleteExpiredFCMTokens
{
    public function handle(NotificationFailedEvent $event): void
    {
        if ($event->channel == FcmChannel::class) {
            if ($event->data['results'][0]['error'] == 'InvalidRegistration') {
                $event->notifiable->update(['device_token' => null]);
            }
        }
    }
}
```

Remember to register your event listeners in the event service provider.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    Benwilkins\FCM\NotificationFailedEvent::class => [
        \App\Listeners\DeleteExpiredFCMTokens::class,
    ],
];
```

## Interpreting a Response

To process any laravel notification channel response check [Laravel Notification Events](https://laravel.com/docs/6.0/notifications#notification-events)

This channel return a json array response: 
```json
 {
    "multicast_id": "number",
    "success": "number",
    "failure": "number",
    "canonical_ids": "number",
    "results": "array"
 }
```

Check [FCM Legacy HTTP Server Protocol](https://firebase.google.com/docs/cloud-messaging/http-server-ref#interpret-downstream) 
for response interpreting documentation.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
