<?php

namespace Benwilkins\FCM;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Events\NotificationFailed;

class NotificationFailedEvent extends NotificationFailed
{
    use Dispatchable;
}
