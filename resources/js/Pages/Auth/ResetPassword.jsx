import { useForm } from '@inertiajs/react';
import { Button, Stack, TextField } from '@mui/material';
import AuthLayout from '../../Layouts/AuthLayout';

export default function ResetPassword({ token, email }) {
    const form = useForm({ token, email: email ?? '', password: '', password_confirmation: '' });

    const field = (name) => ({
        value: form.data[name],
        onChange: (e) => form.setData(name, e.target.value),
        error: !!form.errors[name],
        helperText: form.errors[name],
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('password.update'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="Choose a new password">
            <Stack component="form" spacing={2} onSubmit={submit}>
                <TextField label="Email" type="email" {...field('email')} />
                <TextField label="New password" type="password" autoComplete="new-password" autoFocus {...field('password')} />
                <TextField label="Confirm password" type="password" autoComplete="new-password" {...field('password_confirmation')} />
                <Button type="submit" variant="contained" size="large" disabled={form.processing}>Reset password</Button>
            </Stack>
        </AuthLayout>
    );
}
