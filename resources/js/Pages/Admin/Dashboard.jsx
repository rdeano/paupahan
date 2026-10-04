import { Head, Link } from '@inertiajs/react';
import { Card, CardActionArea, CardContent, Grid2 as Grid, Typography } from '@mui/material';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';

export default function Dashboard({ stats }) {
    const tiles = [
        { label: 'Owners', value: stats.owners, href: route('admin.owners.index') },
        { label: 'All listings', value: stats.listings, href: route('admin.properties.index') },
        { label: 'Public listings', value: stats.published, href: route('admin.properties.index') },
        { label: 'Streets awaiting review', value: stats.pending_streets, href: route('admin.streets.index') },
    ];

    return (
        <WorkspaceLayout>
            <Head title="Admin" />
            <Typography variant="h2" sx={{ mb: 3 }}>Overview</Typography>
            <Grid container spacing={2}>
                {tiles.map((tile) => (
                    <Grid key={tile.label} size={{ xs: 6, md: 3 }}>
                        <Card>
                            <CardActionArea component={Link} href={tile.href}>
                                <CardContent>
                                    <Typography variant="body2" color="text.secondary">{tile.label}</Typography>
                                    <Typography variant="h1" component="p">{tile.value}</Typography>
                                </CardContent>
                            </CardActionArea>
                        </Card>
                    </Grid>
                ))}
            </Grid>
        </WorkspaceLayout>
    );
}
