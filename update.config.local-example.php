<?php
return array(
    // the proxy host should be running on port 443 with https
    'PROXY_HOST' => 'ddns.company.com',
    // generate a long, unique key. must be set when behind reverse proxy
    'TRUSTED_PROXY_KEY' => 'a-very-long-and-random-key'
);
