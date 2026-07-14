<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('pool', function () {
    return true;
});
