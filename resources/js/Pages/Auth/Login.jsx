import { Link, useForm, usePage } from '@inertiajs/react';
import { Alert, Button, Checkbox, FormControlLabel, Link as MuiLink, Stack, TextField, Typography } from '@mui/material';
import AuthLayout from '../../Layouts/AuthLayout';

export default function Login() {
    const { flash } = usePage().props;
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('login'), { onFinish: () => form.reset('password') });
    };

    return (
        <AuthLayout title="Log in" subtitle="Manage your property listings.">
            {flash.status && <Alert severity="success" sx={{ mb: 2 }}>{flash.status}</Alert>}
            {flash.error && <Alert severity="error" sx={{ mb: 2 }}>{flash.error}</Alert>}
            <Stack component="form" spacing={2} onSubmit={submit}>
                <TextField label="Email" type="email" autoComplete="email" autoFocus value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)} error={!!form.errors.email} helperText={form.errors.email} />
                <TextField label="Password" type="password" autoComplete="current-password" value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)} error={!!form.errors.password} helperText={form.errors.password} />
                <FormControlLabel control={<Checkbox checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />} label="Remember me" />
                <Button type="submit" variant="contained" size="large" disabled={form.processing}>Log in</Button>
                <Stack direction="row" justifyContent="space-between">
                    <MuiLink component={Link} href={route('password.request')} variant="body2">Forgot password?</MuiLink>
                    <Typography variant="body2">
                        New here? <MuiLink component={Link} href={route('register')}>Create an account</MuiLink>
                    </Typography>
                </Stack>
            </Stack>
        </AuthLayout>
    );
}
