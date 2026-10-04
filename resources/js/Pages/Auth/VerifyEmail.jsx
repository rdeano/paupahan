import { router, useForm, usePage } from '@inertiajs/react';
import { Alert, Button, Stack } from '@mui/material';
import AuthLayout from '../../Layouts/AuthLayout';

export default function VerifyEmail() {
    const { flash, auth } = usePage().props;
    const form = useForm({});

    return (
        <AuthLayout
            title="Verify your email"
            subtitle={`Verifying ${auth.user?.email} removes the listing limit and adds a Verified badge to your listings. Click the button to get a link.`}
        >
            {flash.status === 'verification-link-sent' && (
                <Alert severity="success" sx={{ mb: 2 }}>A new verification link has been sent.</Alert>
            )}
            <Stack direction="row" spacing={1} justifyContent="space-between">
                <Button variant="contained" disabled={form.processing} onClick={() => form.post(route('verification.send'))}>
                    Send verification email
                </Button>
                <Button onClick={() => router.visit(route('owner.dashboard'))}>Back to my listings</Button>
            </Stack>
        </AuthLayout>
    );
}
