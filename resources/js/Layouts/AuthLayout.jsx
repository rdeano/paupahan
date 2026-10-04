import { Head, Link, usePage } from '@inertiajs/react';
import { Box, Card, CardContent, Container, Typography } from '@mui/material';
import HomeWorkIcon from '@mui/icons-material/HomeWork';

export default function AuthLayout({ title, subtitle, children }) {
    const { appName } = usePage().props;

    return (
        <Box sx={{ minHeight: '100vh', display: 'grid', placeItems: 'center', py: 4 }}>
            <Head title={title} />
            <Container maxWidth="xs">
                <Box component={Link} href={route('home')} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 1, mb: 3, color: 'primary.main', textDecoration: 'none' }}>
                    <HomeWorkIcon />
                    <Typography variant="h6" fontWeight={700}>{appName}</Typography>
                </Box>
                <Card>
                    <CardContent sx={{ p: { xs: 2.5, sm: 4 } }}>
                        <Typography variant="h3" gutterBottom>{title}</Typography>
                        {subtitle && <Typography color="text.secondary" sx={{ mb: 2 }}>{subtitle}</Typography>}
                        {children}
                    </CardContent>
                </Card>
            </Container>
        </Box>
    );
}
