<p style="font-size:1.05rem;">Hello,</p>

@if(isset($client))
    <p style="font-size:1.05rem;">
        <b>{{ $funeralUser->name }}</b> has invited you to become an agent for their client, <b>{{ $client->name }}</b>
        @if(isset($booking))
            (Booking #{{ $booking->id }})
        @endif
        .<br>
        Please click the button below to accept this invitation and complete your registration as a client agent.
    </p>
@else
    <p style="font-size:1.05rem;">
        <b>{{ $funeralUser->name }}</b> has invited you to join their organization as an agent.<br>
        Please click the button below to accept this invitation and complete your agent registration.
    </p>
@endif

<p style="text-align:center; margin:2em 0;">
    <a href="{{ $url }}" style="
        display:inline-block;
        background:#1a73e8;
        color:#fff;
        padding:12px 28px;
        font-size:1.1rem;
        font-weight:600;
        border-radius:8px;
        text-decoration:none;
        box-shadow:0 2px 8px rgba(26,115,232,0.08);
        transition:background 0.2s;
    " onmouseover="this.style.background='#1565c0'">
        Accept Invitation
    </a>
</p>

<p style="font-size:0.98rem; color:#6c757d;">
    If you did not expect this invitation, you may safely ignore this email.
</p>

<hr style="margin:2em 0; border:none; border-top:1px solid #eee;">

<p style="font-size:0.95rem; color:#888;">
    <b>EternaLink Team</b><br>
    For assistance, please contact your funeral home administrator.
</p>
