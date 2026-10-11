import { Head, Link, useForm } from '@inertiajs/react';
import {
    Alert, Button, Card, CardContent, Checkbox, Chip, FormControlLabel, Link as MuiLink, List, ListItem, ListItemText, Stack, TextField, Typography,
} from '@mui/material';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import VerifiedChip from '../../Components/VerifiedChip';

/** Admin: add an owner who hasn't registered, or edit any owner's contact details. */
export default function OwnerForm({ owner }) {
    const editing = Boolean(owner);
    const form = useForm({
        name: owner?.name ?? '',
        email: owner?.email ?? '',
        phone: owner?.phone ?? '',
        show_phone: owner?.show_phone ?? false,
        messenger_username: owner?.messenger_username ?? '',
    });

    const field = (name) => ({
        value: form.data[name],
        onChange: (e) => form.setData(name, e.target.value),
        error: !!form.errors[name],
        helperText: form.errors[name],
    });

    const submit = (event) => {
        event.preventDefault();
        if (editing) form.put(route('admin.owners.update', owner.id), { preserveScroll: true });
        else form.post(route('admin.owners.store'));
    };

    return (
        <WorkspaceLayout maxWidth="sm">
            <Head title={editing ? owner.name : 'Add owner'} />
            <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 3 }}>
                <Typography variant="h2">{editing ? owner.name : 'Add owner'}</Typography>
                {editing && <VerifiedChip verified={owner.verified} addedByAdmin={owner.added_by_admin} />}
            </Stack>

            <Stack spacing={3}>
                {editing && (
                    <Card>
                        <CardContent>
                            <Typography variant="h3" sx={{ mb: 1 }}>Listings</Typography>
                            {owner.properties.length === 0 ? (
                                <Typography variant="body2" color="text.secondary">No listings yet.</Typography>
                            ) : (
                                <List dense disablePadding>
                                    {owner.properties.map((property) => (
                                        <ListItem
                                            key={property.slug}
                                            disableGutters
                                            secondaryAction={<Button size="small" component={Link} href={route('admin.properties.edit', property.slug)}>Edit</Button>}
                                        >
                                            <ListItemText
                                                primary={<MuiLink component={Link} href={route('listings.show', property.slug)}>{property.title}</MuiLink>}
                                                secondary={property.status_label}
                                            />
                                        </ListItem>
                                    ))}
                                </List>
                            )}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent>
                        <Typography variant="h3" gutterBottom>Contact details</Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                            Renters use these to reach the owner from their listings.
                        </Typography>
                        <Stack component="form" spacing={2} onSubmit={submit}>
                            <TextField label="Display name" required {...field('name')} />
                            <TextField
                                label="Facebook Messenger"
                                placeholder="juan.delacruz or m.me/juan.delacruz"
                                {...field('messenger_username')}
                                helperText={form.errors.messenger_username ?? 'Their Facebook username or m.me link. Renters tap "Message on Messenger" to chat with them.'}
                            />
                            {owner?.messenger_url && (
                                <Typography variant="body2">
                                    Test the link: <MuiLink href={owner.messenger_url} target="_blank" rel="noopener">{owner.messenger_url}</MuiLink>
                                </Typography>
                            )}
                            <TextField label="Mobile number (optional)" placeholder="09XX XXX XXXX" {...field('phone')} />
                            <FormControlLabel
                                control={<Checkbox checked={form.data.show_phone} onChange={(e) => form.setData('show_phone', e.target.checked)} />}
                                label="Show their mobile number on their listings"
                            />

                            {editing && owner.can_log_in ? (
                                <TextField label="Login email" value={owner.email} disabled helperText="Only the owner can change their login email." />
                            ) : (
                                <>
                                    <TextField label="Email (optional)" type="email" {...field('email')} />
                                    <Alert severity="info">
                                        {editing ? (
                                            <>This owner can't log in yet. </>
                                        ) : (
                                            <>You can leave this blank and post listings for them. </>
                                        )}
                                        If they want to manage their listings themselves later, add their email and ask them to use
                                        {' '}<Chip size="small" label="Forgot password" /> on the login page to set a password.
                                    </Alert>
                                </>
                            )}

                            <Stack direction="row" spacing={1}>
                                <Button type="submit" variant="contained" disabled={form.processing}>{editing ? 'Save' : 'Add owner'}</Button>
                                <Button component={Link} href={route('admin.owners.index')}>Back to owners</Button>
                            </Stack>
                        </Stack>
                    </CardContent>
                </Card>
            </Stack>
        </WorkspaceLayout>
    );
}
