import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Alert, Button } from '@mui/material';

/** Prompts unverified owners to verify their email; verification is optional but lifts the listing limit. */
export default function VerificationBanner({ verification }) {
    const { flash, auth } = usePage().props;
    const [sending, setSending] = useState(false);

    if (verification.just_verified && verification.verified) {
        return <Alert severity="success" sx={{ mb: 3 }}>Your email is verified. Your listings now show a Verified badge.</Alert>;
    }

    if (verification.verified) return null;

    if (flash.status === 'verification-link-sent') {
        return (
            <Alert severity="success" sx={{ mb: 3 }}>
                We sent a verification link to {auth.user.email}. Open it on this device while logged in.
            </Alert>
        );
    }

    const send = () => router.post(route('verification.send'), {}, {
        preserveScroll: true,
        onStart: () => setSending(true),
        onFinish: () => setSending(false),
    });

    return (
        <Alert
            severity="info"
            sx={{ mb: 3 }}
            action={<Button color="inherit" onClick={send} disabled={sending}>Verify email</Button>}
        >
            Your account is <strong>unverified</strong>. You can have up to {verification.limit} public listings
            {typeof verification.public_count === 'number' ? ` (${verification.public_count} used)` : ''}.
            Verify your email to remove the limit and get a Verified badge.
        </Alert>
    );
}
