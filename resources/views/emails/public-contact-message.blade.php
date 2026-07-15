<h1>New Contact Form Submission</h1>

<p><strong>Name:</strong> {{ $name }}</p>
<p><strong>Email:</strong> {{ $email }}</p>
<p><strong>IP:</strong> {{ $ip }}</p>
<p><strong>User Agent:</strong> {{ $userAgent }}</p>

<h2>Message</h2>
<p>{!! nl2br(e($messageBody)) !!}</p>
