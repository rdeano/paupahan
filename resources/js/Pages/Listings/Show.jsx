import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Alert, Box, Button, Card, CardContent, Chip, Container, Dialog, Divider, Grid2 as Grid, IconButton, Stack, Typography,
} from '@mui/material';
import CloseIcon from '@mui/icons-material/Close';
import ChevronLeftIcon from '@mui/icons-material/ChevronLeft';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import PlaceOutlinedIcon from '@mui/icons-material/PlaceOutlined';
import ChatIcon from '@mui/icons-material/Chat';
import PhoneIcon from '@mui/icons-material/Phone';
import CheckIcon from '@mui/icons-material/Check';
import PublicLayout from '../../Layouts/PublicLayout';
import VerifiedChip from '../../Components/VerifiedChip';
import ApproximateLocationMap from '../../Components/Map/ApproximateLocationMap';
import { formatPrice, plural } from '../../utils';

function Gallery({ photos, title }) {
    const [open, setOpen] = useState(null);

    if (photos.length === 0) {
        return <Box sx={{ aspectRatio: '16 / 9', bgcolor: 'grey.200', borderRadius: 2, display: 'grid', placeItems: 'center', color: 'text.secondary' }}>No photos yet</Box>;
    }

    const cover = photos.find((p) => p.is_cover) ?? photos[0];
    const rest = photos.filter((p) => p.id !== cover.id).slice(0, 4);
    const show = (photo) => setOpen(photos.findIndex((p) => p.id === photo.id));
    const step = (delta) => setOpen((i) => (i + delta + photos.length) % photos.length);

    return (
        <>
            <Grid container spacing={1}>
                <Grid size={{ xs: 12, md: rest.length ? 8 : 12 }}>
                    <Box component="img" src={cover.url} alt={title} onClick={() => show(cover)}
                        sx={{ width: '100%', aspectRatio: '4 / 3', objectFit: 'cover', borderRadius: 2, cursor: 'zoom-in', display: 'block' }} />
                </Grid>
                {rest.length > 0 && (
                    <Grid size={{ xs: 12, md: 4 }} container spacing={1}>
                        {rest.map((photo) => (
                            <Grid key={photo.id} size={{ xs: 3, md: 6 }}>
                                <Box component="img" src={photo.thumb_url} alt="" loading="lazy" onClick={() => show(photo)}
                                    sx={{ width: '100%', aspectRatio: '1', objectFit: 'cover', borderRadius: 2, cursor: 'zoom-in', display: 'block' }} />
                            </Grid>
                        ))}
                    </Grid>
                )}
            </Grid>
            {photos.length > 1 && (
                <Button size="small" sx={{ mt: 1 }} onClick={() => setOpen(0)}>View all {photos.length} photos</Button>
            )}

            <Dialog open={open !== null} onClose={() => setOpen(null)} fullScreen PaperProps={{ sx: { bgcolor: 'black' } }}>
                {open !== null && (
                    <Box sx={{ position: 'relative', height: '100%', display: 'grid', placeItems: 'center' }}>
                        <Box component="img" src={photos[open].url} alt="" sx={{ maxWidth: '100%', maxHeight: '100vh', objectFit: 'contain' }} />
                        <IconButton onClick={() => setOpen(null)} sx={{ position: 'absolute', top: 8, right: 8, color: 'white' }} aria-label="Close"><CloseIcon /></IconButton>
                        {photos.length > 1 && (
                            <>
                                <IconButton onClick={() => step(-1)} sx={{ position: 'absolute', left: 8, color: 'white' }} aria-label="Previous"><ChevronLeftIcon fontSize="large" /></IconButton>
                                <IconButton onClick={() => step(1)} sx={{ position: 'absolute', right: 8, color: 'white' }} aria-label="Next"><ChevronRightIcon fontSize="large" /></IconButton>
                            </>
                        )}
                        <Typography sx={{ position: 'absolute', bottom: 12, color: 'white' }}>{open + 1} / {photos.length}</Typography>
                    </Box>
                )}
            </Dialog>
        </>
    );
}

function Fact({ label, value }) {
    if (value === null || value === undefined || value === '') return null;
    return (
        <Grid size={{ xs: 6, sm: 4 }}>
            <Typography variant="caption" color="text.secondary">{label}</Typography>
            <Typography fontWeight={500}>{value}</Typography>
        </Grid>
    );
}

export default function Show({ listing, preview, editUrl }) {
    const owner = listing.owner;

    return (
        <PublicLayout>
            {/* Description and Open Graph tags are rendered server-side in app.blade.php so Facebook can read them. */}
            <Head title={listing.title} />

            <Container sx={{ py: 3 }}>
                {preview && (
                    <Alert severity="info" sx={{ mb: 2 }}>
                        Preview: this listing is not visible to the public ({listing.status_label}).
                    </Alert>
                )}
                {editUrl && (
                    <Button component={Link} href={editUrl} variant="outlined" sx={{ mb: 2 }}>
                        Edit this listing
                    </Button>
                )}

                <Gallery photos={listing.photos} title={listing.title} />

                <Grid container spacing={3} sx={{ mt: 1 }}>
                    <Grid size={{ xs: 12, md: 8 }}>
                        <Stack direction="row" spacing={1} sx={{ mb: 1 }}>
                            <Chip label={listing.type_label} />
                            {listing.status === 'reserved' && <Chip color="warning" label="Reserved" />}
                        </Stack>
                        <Typography variant="h1" sx={{ fontSize: { xs: '1.5rem', md: '2rem' } }} gutterBottom>{listing.title}</Typography>
                        <Stack direction="row" spacing={0.5} alignItems="center" color="text.secondary">
                            <PlaceOutlinedIcon fontSize="small" />
                            <Typography>{listing.address}</Typography>
                        </Stack>
                        {listing.landmark && <Typography color="text.secondary" sx={{ mt: 0.5 }}>Near {listing.landmark}</Typography>}

                        <Divider sx={{ my: 3 }} />

                        <Grid container spacing={2}>
                            <Fact label="Bedrooms" value={listing.bedrooms > 0 ? listing.bedrooms : 'Studio / room'} />
                            <Fact label="Bathrooms" value={listing.bathrooms} />
                            <Fact label="Floor area" value={listing.floor_area_sqm && `${listing.floor_area_sqm} m²`} />
                            <Fact label="Max occupants" value={listing.max_occupants} />
                            <Fact label="Deposit" value={listing.deposit_months ? plural(listing.deposit_months, 'month') : 'None'} />
                            <Fact label="Advance" value={listing.advance_months ? plural(listing.advance_months, 'month') : 'None'} />
                            <Fact label="Minimum stay" value={listing.min_stay_months && plural(listing.min_stay_months, 'month')} />
                            <Fact label="Available from" value={listing.available_from} />
                        </Grid>

                        <Divider sx={{ my: 3 }} />
                        <Typography variant="h3" gutterBottom>About this place</Typography>
                        <Typography sx={{ whiteSpace: 'pre-line' }}>{listing.description}</Typography>

                        {listing.amenities.length > 0 && (
                            <>
                                <Divider sx={{ my: 3 }} />
                                <Typography variant="h3" gutterBottom>Amenities</Typography>
                                <Grid container spacing={1}>
                                    {listing.amenities.map((name) => (
                                        <Grid key={name} size={{ xs: 12, sm: 6 }} sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
                                            <CheckIcon fontSize="small" color="primary" />
                                            <Typography>{name}</Typography>
                                        </Grid>
                                    ))}
                                </Grid>
                            </>
                        )}

                        {listing.house_rules && (
                            <>
                                <Divider sx={{ my: 3 }} />
                                <Typography variant="h3" gutterBottom>House rules</Typography>
                                <Typography sx={{ whiteSpace: 'pre-line' }}>{listing.house_rules}</Typography>
                            </>
                        )}

                        {listing.location && (
                            <>
                                <Divider sx={{ my: 3 }} />
                                <Typography variant="h3" gutterBottom>Location</Typography>
                                <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                                    The place is within the shaded area. Ask the owner for the exact address when you arrange a viewing.
                                </Typography>
                                <ApproximateLocationMap location={listing.location} radius={listing.location_radius} />
                            </>
                        )}
                    </Grid>

                    <Grid size={{ xs: 12, md: 4 }}>
                        <Card sx={{ position: { md: 'sticky' }, top: { md: 88 } }}>
                            <CardContent>
                                <Typography variant="h2" color="primary">
                                    {formatPrice(listing.price_monthly)}
                                    <Typography component="span" color="text.secondary"> / month</Typography>
                                </Typography>
                                <Divider sx={{ my: 2 }} />
                                <Typography variant="caption" color="text.secondary">Posted by</Typography>
                                <Stack direction="row" spacing={1} alignItems="center">
                                    <Typography fontWeight={600}>
                                        {owner.id ? <Link href={route('owners.show', owner.id)}>{owner.name}</Link> : owner.name}
                                    </Typography>
                                    <VerifiedChip verified={owner.verified} addedByAdmin={owner.added_by_admin} />
                                </Stack>
                                {owner.member_since && <Typography variant="body2" color="text.secondary">Member since {owner.member_since}</Typography>}

                                <Stack spacing={1} sx={{ mt: 2 }}>
                                    {owner.messenger_url ? (
                                        <Button href={owner.messenger_url} target="_blank" rel="noopener" variant="contained" size="large" startIcon={<ChatIcon />}>
                                            Message on Messenger
                                        </Button>
                                    ) : (
                                        <Alert severity="warning">This owner has not added a Messenger contact yet.</Alert>
                                    )}
                                    {owner.phone && (
                                        <Button href={`tel:${owner.phone}`} variant="outlined" startIcon={<PhoneIcon />}>{owner.phone}</Button>
                                    )}
                                </Stack>
                                <Typography variant="caption" color="text.secondary" component="p" sx={{ mt: 2 }}>
                                    Never send a deposit before viewing the place in person.
                                </Typography>
                            </CardContent>
                        </Card>
                    </Grid>
                </Grid>
            </Container>
        </PublicLayout>
    );
}
