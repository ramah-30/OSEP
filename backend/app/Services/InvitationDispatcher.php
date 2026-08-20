<?php

namespace App\Services;

use App\Enums\CommunicationType;
use App\Enums\InvitationChannel;
use App\Enums\InvitationStatus;
use App\Mail\GuestMessageMail;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Models\Notification;
use App\Models\User;
use App\Services\Sms\AfricasTalkingGateway;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Turns "invite this guest" into a real, logged send: it creates the invitation
 * record, renders the message, hands e-mail to the mailer (which logs in dev),
 * advances the delivery lifecycle and mirrors the outcome onto the guest,
 * the communication log and — on failure — the planner's notifications.
 */
class InvitationDispatcher
{
    public function __construct(private readonly AfricasTalkingGateway $sms) {}

    /**
     * Create and (unless scheduled) immediately send an invitation or reminder.
     *
     * @param  array{subject?:string, body?:string, scheduled_for?:\DateTimeInterface|string|null, kind?:string}  $opts
     */
    public function send(
        Guest $guest,
        InvitationChannel $channel,
        ?InvitationTemplate $template,
        ?User $actor,
        array $opts = [],
    ): Invitation {
        $guest->loadMissing('event');
        $event = $guest->event;
        $kind = $opts['kind'] ?? 'invitation';
        $noun = $kind === 'reminder' ? 'Reminder' : 'Invitation';

        $invitation = $event->invitations()->create([
            'guest_id' => $guest->id,
            'template_id' => $template?->id,
            'created_by' => $actor?->id,
            'channel' => $channel->value,
            'status' => InvitationStatus::Draft->value,
            'subject' => $opts['subject'] ?? $this->defaultSubject($event->title, $template, $kind),
            'body' => $opts['body'] ?? $this->renderBody($guest, $template, $kind),
            'scheduled_for' => $opts['scheduled_for'] ?? null,
            'meta' => ['kind' => $kind],
        ]);

        if ($invitation->scheduled_for) {
            $invitation->update(['status' => InvitationStatus::Scheduled->value]);
            $this->logDelivery($invitation, 'scheduled', 'Queued for '.$invitation->scheduled_for->toDayDateTimeString());
            $guest->forceFill(['invitation_status' => InvitationStatus::Scheduled->value])->save();
            $this->logCommunication($guest, $this->commType($kind), $channel, "{$noun} scheduled", $actor);

            return $invitation;
        }

        return $this->dispatch($invitation, $actor);
    }

    /**
     * Deliver a draft/scheduled invitation now. Safe to call from the scheduler.
     */
    public function dispatch(Invitation $invitation, ?User $actor = null): Invitation
    {
        $guest = $invitation->guest()->with('event')->first();
        $channel = $invitation->channel;
        $kind = $invitation->meta['kind'] ?? 'invitation';
        $noun = $kind === 'reminder' ? 'Reminder' : 'Invitation';

        try {
            if ($channel === InvitationChannel::Email) {
                if (! $guest->email) {
                    throw new \RuntimeException('Guest has no email address.');
                }
                Mail::to($guest->email)->send(new GuestMessageMail($invitation->subject, $invitation->body));
            } elseif ($channel === InvitationChannel::Sms) {
                if (! $guest->phone) {
                    throw new \RuntimeException('Guest has no phone number.');
                }
                $result = $this->sms->send($guest->phone, $this->renderSmsBody($guest, $invitation));
                $invitation->update([
                    'meta' => array_merge($invitation->meta ?? [], [
                        'provider' => 'africastalking',
                        'provider_message_id' => $result['messageId'],
                    ]),
                ]);
            }
            // WhatsApp still has no server gateway — the planner reaches those
            // guests through the per-guest deep link on the Guest List.

            $now = now();
            $invitation->update(['status' => InvitationStatus::Sent->value, 'sent_at' => $now]);
            $this->logDelivery($invitation, 'sent', $channel->label().' dispatched');

            // Dev environments have no delivery webhook, so we optimistically mark
            // it delivered; a real provider would flip this asynchronously.
            $invitation->update(['status' => InvitationStatus::Delivered->value, 'delivered_at' => $now]);
            $this->logDelivery($invitation, 'delivered', 'Delivered to '.($guest->email ?: $channel->label()));

            $guest->forceFill(['invitation_status' => InvitationStatus::Delivered->value])->save();
            $this->logCommunication($guest, $this->commType($kind), $channel, "{$noun} sent", $actor);
        } catch (Throwable $e) {
            $invitation->update([
                'status' => InvitationStatus::Failed->value,
                'failed_reason' => $e->getMessage(),
            ]);
            $this->logDelivery($invitation, 'failed', $e->getMessage());
            $guest->forceFill(['invitation_status' => InvitationStatus::Failed->value])->save();
            $this->logCommunication($guest, $this->commType($kind), $channel, "{$noun} failed: ".$e->getMessage(), $actor);
            $this->notifyFailure($guest, $e->getMessage(), $noun);
        }

        return $invitation;
    }

    /**
     * The public RSVP URL for a guest, pointing at the SPA.
     */
    public function rsvpUrl(Guest $guest): string
    {
        $base = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        return $base.'/rsvp/'.$guest->rsvp_token;
    }

    private function defaultSubject(string $eventTitle, ?InvitationTemplate $template, string $kind = 'invitation'): string
    {
        if ($kind === 'reminder') {
            return "Reminder: please RSVP for {$eventTitle}";
        }

        if ($template?->subject) {
            return $template->subject;
        }

        return "You're invited to {$eventTitle}";
    }

    private function renderBody(Guest $guest, ?InvitationTemplate $template, string $kind = 'invitation'): string
    {
        $event = $guest->event;
        $url = $this->rsvpUrl($guest);
        $date = $event->event_date?->format('l, F j, Y') ?? 'To be announced';
        $greetingName = $guest->first_name ?: $guest->full_name;

        $intro = $kind === 'reminder'
            ? "This is a friendly reminder to let us know whether you can join us for {$event->title}. We'd love to have you there."
            : ($template?->body
                ? $this->personalise($template->body, $guest)
                : "We would be delighted to have you join us for {$event->title}.");

        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#1e293b">
          <h1 style="color:#1e3a8a">Hello {$greetingName},</h1>
          <p style="font-size:15px;line-height:1.6">{$intro}</p>
          <table style="margin:16px 0;font-size:14px">
            <tr><td style="padding:4px 12px 4px 0;color:#64748b">Event</td><td><strong>{$event->title}</strong></td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#64748b">Date</td><td>{$date}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#64748b">Venue</td><td>{$event->venue}</td></tr>
          </table>
          <p style="margin:24px 0">
            <a href="{$url}" style="background:#1e3a8a;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">Respond to your invitation</a>
          </p>
          <p style="font-size:12px;color:#94a3b8">Or open this link: {$url}</p>
        </div>
        HTML;
    }

    /**
     * A compact plain-text message for SMS — no HTML, just the essentials and the
     * RSVP link, kept short to stay within a segment or two.
     */
    private function renderSmsBody(Guest $guest, Invitation $invitation): string
    {
        $event = $guest->event;
        $name = $guest->first_name ?: $guest->full_name;
        $url = $this->rsvpUrl($guest);
        $date = $event->event_date?->format('M j, Y');
        $kind = $invitation->meta['kind'] ?? 'invitation';

        if ($kind === 'reminder') {
            return "Hi {$name}, a friendly reminder to RSVP for {$event->title}"
                .($date ? " on {$date}" : '').". Respond here: {$url}";
        }

        return "Hi {$name}! You're invited to {$event->title}"
            .($date ? " on {$date}" : '').". Please RSVP: {$url}";
    }

    private function personalise(string $body, Guest $guest): string
    {
        return strtr($body, [
            '{{first_name}}' => $guest->first_name ?? '',
            '{{last_name}}' => $guest->last_name ?? '',
            '{{full_name}}' => $guest->full_name ?? '',
            '{{event}}' => $guest->event?->title ?? '',
        ]);
    }

    private function logDelivery(Invitation $invitation, string $status, string $detail): void
    {
        $invitation->deliveryLogs()->create([
            'status' => $status,
            'channel' => $invitation->channel->value,
            'detail' => $detail,
            'occurred_at' => now(),
        ]);
    }

    private function logCommunication(Guest $guest, CommunicationType $type, InvitationChannel $channel, string $title, ?User $actor): void
    {
        $guest->communicationLogs()->create([
            'event_id' => $guest->event_id,
            'created_by' => $actor?->id,
            'type' => $type->value,
            'channel' => $channel->value,
            'title' => $title,
        ]);
    }

    private function commType(string $kind): CommunicationType
    {
        return $kind === 'reminder' ? CommunicationType::Reminder : CommunicationType::Invitation;
    }

    private function notifyFailure(Guest $guest, string $reason, string $noun = 'Invitation'): void
    {
        $plannerId = $guest->event?->planner_id;

        if (! $plannerId) {
            return;
        }

        Notification::create([
            'user_id' => $plannerId,
            'type' => 'invitation_failed',
            'title' => strtolower($noun).' delivery failed',
            'message' => "Could not deliver the {$noun} to {$guest->full_name}: {$reason}",
            'data' => ['event_id' => $guest->event_id, 'guest_id' => $guest->id],
        ]);
    }
}
