<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders', function () {
    return true;
});
Broadcast::channel('recommendations', function () {
    return true;
});
