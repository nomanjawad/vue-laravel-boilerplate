New contact form submission

Name:    {{ $senderName }}
Email:   {{ $senderEmail }}
Phone:   {{ $phone ?: 'N/A' }}
Subject: {{ $subjectLine }}

Message:
{{ $messageBody }}
