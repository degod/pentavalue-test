<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders', function () {
    return true;
});
