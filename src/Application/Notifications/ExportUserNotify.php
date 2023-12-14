<?php

namespace Nero\BackpackExport\Application\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportUserNotify extends Notification
{
    use Queueable;

    /**
     * File name
     */
    private string $file_name;

    /**
     * Path to file, included file name
     */
    private string $path_to_file;

    /**
     * @param $path_to_file
     * @param $file_name
     */
    public function __construct($path_to_file, $file_name)
    {
        $this->onQueue(config('backpack_export.queue'));
        $this->path_to_file = $path_to_file;
        $this->file_name = $file_name;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = sprintf(config('backpack_export.download_url'), $this->file_name);

        return (new MailMessage)
            ->greeting(__('backpack_export.mail.greeting'))
            ->subject(__('backpack_export.mail.subject'))
            ->line(__('backpack_export.mail.line'))
            ->action(__('backpack_export.mail.action', ['file_name' => $this->file_name]), $url);
    }
}
