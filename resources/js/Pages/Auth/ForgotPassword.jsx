import { useForm, usePage } from '@inertiajs/react';
import { Alert, Button, Stack, TextField } from '@mui/material';
import AuthLayout from '../../Layouts/AuthLayout';

export default function ForgotPassword() {
    const { flash } = usePage().props;
    const form = useForm({ email: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('password.email'));
    };

    return (
        <AuthLayout title="Forgot your password?" subtitle="Enter your email and we'll send you a reset link.">
            {flash.status && <Alert severity="success" sx={{ mb: 2 }}>{flash.status}</Alert>}
            <Stack component="form" spacing={2} onSubmit={submit}>
                <TextField label="Email" type="email" autoFocus value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)} error={!!form.errors.email} helperText={form.errors.email} />
                <Button type="submit" variant="contained" size="large" disabled={form.processing}>Email reset link</Button>
            </Stack>
        </AuthLayout>
    );
}
