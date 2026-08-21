<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('whatsapp.account.{id}', function ($user, $id) {
    return true; // Authorize any logged in user for this demo
});

Broadcast::channel('whatsapp.conversation.{id}', function ($user, $id) {
    return true; // Authorize any logged in user for this demo
});

Broadcast::channel('whatsapp.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
