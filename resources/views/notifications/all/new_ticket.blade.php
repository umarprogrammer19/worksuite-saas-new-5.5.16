@php
    $user = \App\Models\User::withoutGlobalScope(\App\Scopes\ActiveScope::class)->find($notification->data['user_id']);
@endphp

@if ($user)
    <x-cards.notification
        :notification="$notification"
        :link="route('tickets.show', $notification->data['ticket_number'])"
        :image="$user->image_url"
        :title="__('email.newTicket.subject') . ' #' . $notification->data['ticket_number']"
        :text="$notification->data['subject']"
        :time="$notification->created_at"
    />
@endif
