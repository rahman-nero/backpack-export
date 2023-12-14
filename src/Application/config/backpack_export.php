<?php

use Nero\BackpackExport\Application\Notifications\ExportUserNotify;

return [

    /**
     * Timeout for Job that makes exports
     */
    'timeout'           => 240,

    /**
     * Queue's name for Job that makes exports
     */
    'queue'             => 'default',

    /**
     * The notification class that will be called after the export is done
     * Default value notifies user by sending email which includes also a link to download the export.
     *
     * It has to be extended from Illuminate\Notifications\Notification
     */
    'notify'            => ExportUserNotify::class,

    /**
     * User model to identify who send export request
     */
    'user_model'        => \App\Models\User::class,

    /**
     * Chunking rows from table while exporting
     */
    'chunk'             => 5000,

    /**
     * Directory where the exported file will be saved. It has to be as absolute path.
     *
     * Example: /home/user/project/storage/app/public/reports
     */
    'directory_to_save' => storage_path('app/public'),

    /**
     * Url to download the export file, it will be included in the letter that will be sent after the export is done
     *
     * Example: https://localhost/storage/%s. `%s` will be replaced with "Export ... .cvs|xlsx"
     */
    'download_url'      => env('APP_URL') . '/storage/%s',
];
