<?php
return array(
    'salt' => md5(Arr::get($_SERVER, 'HTTP_HOST')),
    'upload_url' => '/!/upload/',
    'upload_dir' => DOCROOT . '!/upload/',
);