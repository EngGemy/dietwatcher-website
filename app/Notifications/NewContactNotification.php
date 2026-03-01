<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Contact $contact)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Contact Message') . ': ' . $this->contact->subject)
            ->greeting(__('Hello Admin,'))
            ->line(__('You have received a new contact message from') . ' ' . $this->contact->full_name)
            ->line(__('Email') . ': ' . $this->contact->email)
            ->line(__('Subject') . ': ' . $this->contact->subject)
            ->line(__('Message') . ':')
            ->line($this->contact->message)
            ->action(__('View in Admin'), url('/admin/contacts/' . $this->contact->id . '/edit'))
            ->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_id' => $this->contact->id,
            'name' => $this->contact->full_name,
            'email' => $this->contact->email,
            'subject' => $this->contact->subject,
        ];
    }
}
