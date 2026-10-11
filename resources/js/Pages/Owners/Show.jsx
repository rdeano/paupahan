import { Head } from '@inertiajs/react';
import { Avatar, Button, Container, Grid2 as Grid, Stack, Typography } from '@mui/material';
import ChatIcon from '@mui/icons-material/Chat';
import PublicLayout from '../../Layouts/PublicLayout';
import ListingCard from '../../Components/ListingCard';
import Paginator from '../../Components/Paginator';
import VerifiedChip from '../../Components/VerifiedChip';

export default function Show({ owner, listings }) {
    return (
        <PublicLayout>
            <Head title={`${owner.name}'s listings`} />
            <Container sx={{ py: 4 }}>
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems={{ sm: 'center' }} sx={{ mb: 4 }}>
                    <Avatar sx={{ width: 64, height: 64, bgcolor: 'primary.main', fontSize: 28 }}>{owner.name.charAt(0)}</Avatar>
                    <div style={{ flexGrow: 1 }}>
                        <Stack direction="row" spacing={1} alignItems="center">
                            <Typography variant="h2">{owner.name}</Typography>
                            <VerifiedChip verified={owner.verified} addedByAdmin={owner.added_by_admin} />
                        </Stack>
                        {owner.member_since && <Typography color="text.secondary">Member since {owner.member_since}</Typography>}
                    </div>
                    {owner.messenger_url && (
                        <Button href={owner.messenger_url} target="_blank" rel="noopener" variant="contained" startIcon={<ChatIcon />}>
                            Message on Messenger
                        </Button>
                    )}
                </Stack>

                <Typography variant="h3" gutterBottom>Listings ({listings.total})</Typography>
                {listings.data.length === 0 ? (
                    <Typography color="text.secondary">No available listings right now.</Typography>
                ) : (
                    <Grid container spacing={2}>
                        {listings.data.map((listing) => (
                            <Grid key={listing.id} size={{ xs: 12, sm: 6, md: 4 }}>
                                <ListingCard listing={listing} />
                            </Grid>
                        ))}
                    </Grid>
                )}
                <Paginator paginator={listings} />
            </Container>
        </PublicLayout>
    );
}
