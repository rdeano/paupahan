import { Link } from '@inertiajs/react';
import { Box, Card, CardActionArea, CardContent, CardMedia, Chip, Stack, Typography } from '@mui/material';
import PlaceOutlinedIcon from '@mui/icons-material/PlaceOutlined';
import ImageNotSupportedOutlinedIcon from '@mui/icons-material/ImageNotSupportedOutlined';
import { formatPrice, plural } from '../utils';

export default function ListingCard({ listing }) {
    return (
        <Card sx={{ height: '100%' }}>
            <CardActionArea component={Link} href={route('listings.show', listing.slug)} sx={{ height: '100%', alignItems: 'stretch' }}>
                {listing.cover_url ? (
                    <CardMedia component="img" image={listing.cover_url} alt={listing.title} loading="lazy" sx={{ aspectRatio: '4 / 3' }} />
                ) : (
                    <Box sx={{ aspectRatio: '4 / 3', bgcolor: 'grey.200', display: 'grid', placeItems: 'center', color: 'grey.500' }}>
                        <ImageNotSupportedOutlinedIcon fontSize="large" />
                    </Box>
                )}
                <CardContent>
                    <Stack direction="row" spacing={1} sx={{ mb: 1 }}>
                        <Chip size="small" label={listing.type_label} />
                        {listing.status === 'reserved' && <Chip size="small" color="warning" label="Reserved" />}
                    </Stack>
                    <Typography variant="h6" color="primary" fontWeight={700}>
                        {formatPrice(listing.price_monthly)}
                        <Typography component="span" variant="body2" color="text.secondary"> / month</Typography>
                    </Typography>
                    <Typography variant="subtitle1" fontWeight={500} noWrap title={listing.title}>
                        {listing.title}
                    </Typography>
                    <Stack direction="row" spacing={0.5} alignItems="flex-start" sx={{ color: 'text.secondary', mt: 0.5 }}>
                        <PlaceOutlinedIcon fontSize="small" sx={{ mt: '2px' }} />
                        <Typography variant="body2">{listing.address}</Typography>
                    </Stack>
                    {listing.landmark && (
                        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                            Near {listing.landmark}
                        </Typography>
                    )}
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                        {listing.bedrooms > 0 ? plural(listing.bedrooms, 'bedroom') : 'Studio / room'} · {plural(listing.bathrooms, 'bath')}
                    </Typography>
                </CardContent>
            </CardActionArea>
        </Card>
    );
}
