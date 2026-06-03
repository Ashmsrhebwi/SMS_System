<?php

return [
    'rate_limit_delay' => env('SMS_RATE_LIMIT_DELAY', 1), // seconds between messages
    'opt_out_text' => env('SMS_OPT_OUT_TEXT', 'To stop receiving messages, visit: {opt_out_url}'),
    'whatsapp_url' => env('SMS_WHATSAPP_URL', 'https://wa.me/'),
];
