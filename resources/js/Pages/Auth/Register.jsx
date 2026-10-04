import { Link, useForm } from '@inertiajs/react';
import { Button, Link as MuiLink, Stack, TextField, Typography } from '@mui/material';
import AuthLayout from '../../Layouts/AuthLayout';

export default function Register() {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    const field = (name) => ({
        value: form.data[name],
        onChange: (e) => form.setData(name, e.target.value),
        error: !!form.errors[name],
        helperText: form.errors[name],
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="List your property" subtitle="Create a free owner account to post your rentals.">
            <Stack component="form" spacing={2} onSubmit={submit}>
                <TextField label="Your name" autoComplete="name" autoFocus {...field('name')} />
                <TextField label="Email" type="email" autoComplete="email" {...field('email')} />
                <TextField label="Password" type="password" autoComplete="new-password" {...field('password')} />
                <TextField label="Confirm password" type="password" autoComplete="new-password" {...field('password_confirmation')} />
                <Button type="submit" variant="contained" size="large" disabled={form.processing}>Create account</Button>
                <Typography variant="body2">
                    Already registered? <MuiLink component={Link} href={route('login')}>Log in</MuiLink>
                </Typography>
            </Stack>
        </AuthLayout>
    );
}
