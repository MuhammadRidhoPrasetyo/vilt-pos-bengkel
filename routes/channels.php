<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('store.{storeId}', function (User $user, $storeId) {
    // Owners, super-admins, or admins not tied to a single store can listen to any store
    if ($user->hasRole('owner') || $user->hasRole('super-admin') || $user->hasRole('admin') || empty($user->store_id)) {
        return true;
    }

    return (string) $user->store_id === (string) $storeId;
});
