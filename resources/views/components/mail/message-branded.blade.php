@props(['subject' => null])

@include('emails.layouts.branded', ['subject' => $subject, 'slot' => $slot])
