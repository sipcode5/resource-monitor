<?php

use Illuminate\Support\Facades\Broadcast;

// Public channels – no auth required
Broadcast::channel('metrics', fn () => true);
Broadcast::channel('notifications', fn () => true);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
