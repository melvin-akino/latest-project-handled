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
            ->line('We offer you a genuinely unique online sports betting experience with a single click!')
            ->line('Simutaneous Execution')
            ->line('Multiline.io brings you real-time odds offered by multiple bookmakers and exchanges. Orders are matched simultaneously at all available prices that satisfy your requirements.')
            ->line('Global Liquidity')
            ->line('Multiline.io is integrated with many of the world’s biggest bookmakers and betting exchanges. This includes Singbet, ISN, PIN, ISC, SBC, and SBO.')
            ->line('Market-based Prices')
            ->line('Orders are matched in price descending order. ‘Top down execution’ ensures that you always get the best price available in the market.')
            ->line('Comprehensive Coverage')
            ->line('Early market, game day and in-running offers on Asian Handicaps and all other major markets for Football, Basketball, American Football, Baseball, and E-Sports.')
            ->line('Speed')
            ->line('Multiline.io is the fastest sports betting software of its kind. We continually invest in the latest technologies to deliver the fastest price retrieval and bet placement in the market.')
            ->line('Thank you for using our application!');
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
