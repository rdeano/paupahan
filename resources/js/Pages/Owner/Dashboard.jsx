import { Head, Link, router } from '@inertiajs/react';
import {
    Alert, Box, Button, Card, CardContent, Chip, FormControl, IconButton, MenuItem, Select, Stack, Tooltip, Typography,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import VisibilityIcon from '@mui/icons-material/Visibility';
import DeleteOutlineIcon from '@mui/icons-material/DeleteOutline';
import ImageNotSupportedOutlinedIcon from '@mui/icons-material/ImageNotSupportedOutlined';
import { useState } from 'react';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import VerificationBanner from '../../Components/VerificationBanner';
import { formatPrice } from '../../utils';

const STATUS_COLORS = { draft: 'default', available: 'success', reserved: 'warning', occupied: 'info', archived: 'default' };

export default function Dashboard({ properties, statuses, missingMessenger, verification }) {
    const [statusError, setStatusError] = useState(null);

    const changeStatus = (property, status) =>
        router.patch(route('owner.properties.status', property.slug), { status }, {
            preserveScroll: true,
            onSuccess: () => setStatusError(null),
            onError: (errors) => setStatusError(errors.status ?? null),
        });

    const remove = (property) => {
        if (window.confirm(`Delete "${property.title}"? This cannot be undone.`)) {
            router.delete(route('owner.properties.destroy', property.slug));
        }
    };

    return (
        <WorkspaceLayout>
            <Head title="My listings" />

            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                <Typography variant="h2">My listings</Typography>
                <Button component={Link} href={route('owner.properties.create')} variant="contained" startIcon={<AddIcon />}>
                    Add property
                </Button>
            </Stack>

            <VerificationBanner verification={verification} />

            {statusError && (
                <Alert severity="error" sx={{ mb: 3 }} onClose={() => setStatusError(null)}>{statusError}</Alert>
            )}

            {missingMessenger && (
                <Alert severity="warning" sx={{ mb: 3 }} action={<Button component={Link} href={route('owner.profile.edit')} color="inherit">Add it</Button>}>
                    Add your Facebook Messenger username so renters can contact you.
                </Alert>
            )}

            {properties.length === 0 ? (
                <Card>
                    <CardContent sx={{ textAlign: 'center', py: 6 }}>
                        <Typography variant="h3" gutterBottom>No properties yet</Typography>
                        <Typography color="text.secondary" sx={{ mb: 2 }}>Post your first rental. It only takes a few minutes.</Typography>
                        <Button component={Link} href={route('owner.properties.create')} variant="contained">Add property</Button>
                    </CardContent>
                </Card>
            ) : (
                <Stack spacing={2}>
                    {properties.map((property) => (
                        <Card key={property.id}>
                            <Stack direction={{ xs: 'column', sm: 'row' }}>
                                {property.cover_url ? (
                                    <Box component="img" src={property.cover_url} alt="" sx={{ width: { xs: '100%', sm: 180 }, height: { xs: 160, sm: 'auto' }, objectFit: 'cover' }} />
                                ) : (
                                    <Box sx={{ width: { xs: '100%', sm: 180 }, minHeight: 120, bgcolor: 'grey.200', display: 'grid', placeItems: 'center', color: 'grey.500' }}>
                                        <ImageNotSupportedOutlinedIcon />
                                    </Box>
                                )}
                                <CardContent sx={{ flexGrow: 1 }}>
                                    <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.5 }}>
                                        <Chip size="small" label={property.status_label} color={STATUS_COLORS[property.status]} />
                                        <Typography variant="body2" color="text.secondary">{property.type_label} · updated {property.updated_at}</Typography>
                                    </Stack>
                                    <Typography variant="h6">{property.title}</Typography>
                                    <Typography color="primary" fontWeight={700}>{formatPrice(property.price_monthly)} / month</Typography>
                                    <Typography variant="body2" color="text.secondary">{property.address}</Typography>
                                    {property.photos_count === 0 && (
                                        <Typography variant="body2" color="warning.main" sx={{ mt: 0.5 }}>No photos yet</Typography>
                                    )}
                                </CardContent>
                                <Stack direction={{ xs: 'row', sm: 'column' }} spacing={1} sx={{ p: 2, alignItems: { sm: 'flex-end' }, justifyContent: 'space-between' }}>
                                    <FormControl size="small" sx={{ minWidth: 140 }}>
                                        <Select value={property.status} onChange={(e) => changeStatus(property, e.target.value)} aria-label="Status">
                                            {statuses.map((s) => <MenuItem key={s.value} value={s.value}>{s.label}</MenuItem>)}
                                        </Select>
                                    </FormControl>
                                    <Box>
                                        <Tooltip title="View">
                                            <IconButton component={Link} href={route('listings.show', property.slug)}><VisibilityIcon /></IconButton>
                                        </Tooltip>
                                        <Tooltip title="Edit">
                                            <IconButton component={Link} href={route('owner.properties.edit', property.slug)}><EditIcon /></IconButton>
                                        </Tooltip>
                                        <Tooltip title="Delete">
                                            <IconButton onClick={() => remove(property)}><DeleteOutlineIcon /></IconButton>
                                        </Tooltip>
                                    </Box>
                                </Stack>
                            </Stack>
                        </Card>
                    ))}
                </Stack>
            )}
        </WorkspaceLayout>
    );
}
