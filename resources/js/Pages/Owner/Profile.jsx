import { Head, useForm } from '@inertiajs/react';
import { Button, Card, CardContent, Checkbox, FormControlLabel, Link, Stack, TextField, Typography } from '@mui/material';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import VerificationBanner from '../../Components/VerificationBanner';
import VerifiedChip from '../../Components/VerifiedChip';

export default function Profile({ profile, verification }) {
    const form = useForm({
        name: profile.name ?? '',
        phone: profile.phone ?? '',
        show_phone: profile.show_phone ?? false,
        messenger_username: profile.messenger_username ?? '',
    });

    const password = useForm({ current_password: '', password: '', password_confirmation: '' });

    const field = (f, name) => ({
        value: f.data[name],
        onChange: (e) => f.setData(name, e.target.value),
        error: !!f.errors[name],
        helperText: f.errors[name],
    });

    const saveProfile = (event) => {
        event.preventDefault();
        form.put(route('owner.profile.update'), { preserveScroll: true });
    };

    const savePassword = (event) => {
        event.preventDefault();
        password.put(route('user-password.update'), {
            preserveScroll: true,
            errorBag: 'updatePassword',
            onSuccess: () => password.reset(),
        });
    };

    return (
        <WorkspaceLayout maxWidth="sm">
            <Head title="Profile" />
            <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 3 }}>
                <Typography variant="h2">Profile</Typography>
                <VerifiedChip verified={verification.verified} />
            </Stack>

            <VerificationBanner verification={verification} />

            <Stack spacing={3}>
                <Card>
                    <CardContent>
                        <Typography variant="h3" gutterBottom>Contact details</Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Renters use these to reach you from your listings.
                        </Typography>
                        <Stack component="form" spacing={2} onSubmit={saveProfile}>
                            <TextField label="Display name" {...field(form, 'name')} />
                            <TextField
                                label="Facebook Messenger"
                                placeholder="juan.delacruz or m.me/juan.delacruz"
                                {...field(form, 'messenger_username')}
                                helperText={form.errors.messenger_username ?? 'Your Facebook username or m.me link. Renters will tap "Message on Messenger" to chat with you.'}
                            />
                            {profile.messenger_url && (
                                <Typography variant="body2">
                                    Test your link: <Link href={profile.messenger_url} target="_blank" rel="noopener">{profile.messenger_url}</Link>
                                </Typography>
                            )}
                            <TextField label="Mobile number (optional)" placeholder="09XX XXX XXXX" {...field(form, 'phone')} />
                            <FormControlLabel
                                control={<Checkbox checked={form.data.show_phone} onChange={(e) => form.setData('show_phone', e.target.checked)} />}
                                label="Show my mobile number on my listings"
                            />
                            <Button type="submit" variant="contained" disabled={form.processing} sx={{ alignSelf: 'flex-start' }}>Save</Button>
                        </Stack>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <Typography variant="h3" gutterBottom>Change password</Typography>
                        <Stack component="form" spacing={2} onSubmit={savePassword}>
                            <TextField label="Current password" type="password" autoComplete="current-password" {...field(password, 'current_password')} />
                            <TextField label="New password" type="password" autoComplete="new-password" {...field(password, 'password')} />
                            <TextField label="Confirm new password" type="password" autoComplete="new-password" {...field(password, 'password_confirmation')} />
                            <Button type="submit" variant="outlined" disabled={password.processing} sx={{ alignSelf: 'flex-start' }}>Update password</Button>
                        </Stack>
                    </CardContent>
                </Card>
            </Stack>
        </WorkspaceLayout>
    );
}
