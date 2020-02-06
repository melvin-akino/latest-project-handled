<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationMail extends Notification
{
    use Queueable;

    protected $userName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($userName)
    {
        $this->userName = $userName;
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
        return (new MailMessage)
            ->line('Hi ' . $this->userName . ',')
            ->line('Welcome to multiline.io!')
            // HEADER
                ->line(trans('mail.registration.header'))
            // SIMULTANEOUS EXECUTION
                ->line(trans('mail.registration.simultaneous-execution.title'))
                ->line(trans('mail.registration.simultaneous-execution.content'))
            // GLOBAL LIQUIDITY
                ->line(trans('mail.registration.global-liquidity.title'))
                ->line(trans('mail.registration.global-liquidity.content'))
            // MARKET-BASED PRICES
                ->line(trans('mail.registration.market-based-prices.title'))
                ->line(trans('mail.registration.market-based-prices.content'))
            // COMPREHENSIVE COVERAGE
                ->line(trans('mail.registration.comprehensive-coverage.title'))
                ->line(trans('mail.registration.comprehensive-coverage.content'))
            // SPEED
                ->line(trans('mail.registration.speed.title'))
                ->line(trans('mail.registration.speed.content'))
            // FOOTER
            ->line(trans('mail.registration.footer'));
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
