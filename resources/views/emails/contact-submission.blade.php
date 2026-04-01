<h2>New Contact Form Submission</h2>
<p><strong>Name:</strong> {{ $payload['name'] }}</p>
<p><strong>Email:</strong> {{ $payload['email'] }}</p>
<p><strong>Subject:</strong> {{ $payload['subject'] }}</p>
<p><strong>Message:</strong></p>
<p>{!! nl2br(e($payload['message'])) !!}</p>
