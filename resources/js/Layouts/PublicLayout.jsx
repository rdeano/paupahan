import { Link, usePage } from '@inertiajs/react';
import { AppBar, Box, Button, Container, Link as MuiLink, Toolbar, Typography } from '@mui/material';
import HomeWorkIcon from '@mui/icons-material/HomeWork';
import FlashMessage from '../Components/FlashMessage';

export default function PublicLayout({ children }) {
    const { auth, appName } = usePage().props;
    const user = auth.user;

    return (
        <Box sx={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
            <AppBar position="sticky" color="inherit" elevation={0} sx={{ borderBottom: 1, borderColor: 'divider' }}>
                <Toolbar sx={{ gap: 1 }}>
                    <Box
                        component={Link}
                        href={route('home')}
                        sx={{ display: 'flex', alignItems: 'center', gap: 1, color: 'primary.main', textDecoration: 'none', flexGrow: 1 }}
                    >
                        <HomeWorkIcon />
                        <Typography variant="h6" fontWeight={700}>
                            {appName}
                        </Typography>
                    </Box>
                    {user ? (
                        <Button
                            component={Link}
                            href={user.is_admin ? route('admin.dashboard') : route('owner.dashboard')}
                            variant="contained"
                        >
                            {user.is_admin ? 'Admin' : 'My listings'}
                        </Button>
                    ) : (
                        <>
                            <Button component={Link} href={route('login')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
                                Log in
                            </Button>
                            <Button component={Link} href={route('register')} variant="contained">
                                List your property
                            </Button>
                        </>
                    )}
                </Toolbar>
            </AppBar>

            <Box component="main" sx={{ flexGrow: 1 }}>
                {children}
            </Box>

            <Box component="footer" sx={{ py: 3, mt: 4, borderTop: 1, borderColor: 'divider', bgcolor: 'background.paper' }}>
                <Container>
                    <Typography variant="body2" color="text.secondary">
                        © {new Date().getFullYear()} {appName} · Rentals in Davao City
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                        Street data ©{' '}
                        <MuiLink href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">
                            OpenStreetMap contributors
                        </MuiLink>
                        . Barangay list: PSA PSGC.
                    </Typography>
                </Container>
            </Box>

            <FlashMessage />
        </Box>
    );
}
