<?php

namespace App\Notifications;

use App\Auth\PasswordReset;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($passwordReset, $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $data = [
            'name'       => $this->user->name,
            'email'      => $this->user->email,
            'ip_address' => Request::ip(),
            'datetime'   => Carbon::now()->format('jS \o\f F, Y g:i:s a')
        ];

        return (new MailMessage)->markdown('mail.reset-success', $data)
            ->subject(trans('mail.password.reset.subject'));

        // return (new MailMessage)
        //     ->line(trans('mail.password.reset.success'))
        //     ->line(trans('mail.password.reset.body'))
        //     ->line(trans('mail.password.reset.footer'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}