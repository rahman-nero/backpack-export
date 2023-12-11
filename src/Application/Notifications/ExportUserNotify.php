<?php

namespace Nero\BackpackExport\Application\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportUserNotify extends Notification
{
    use Queueable;

    /**
     * Название файла
     */
    private string $file_name;

    /**
     * Путь до файла, включительно с именем
     */
    private mixed $path_to_file;

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
            ->greeting('Здравствуйте!')
            ->subject('Экспорт')
            ->line('Сформировался запрошенный вами экспорт!')
            ->action("Скачать экспорт ({$this->file_name})", $url);
    }
}
