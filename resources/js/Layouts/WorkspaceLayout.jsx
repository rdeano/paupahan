import { Link, router, usePage } from '@inertiajs/react';
import { AppBar, Box, Button, Container, Tab, Tabs, Toolbar, Typography } from '@mui/material';
import HomeWorkIcon from '@mui/icons-material/HomeWork';
import FlashMessage from '../Components/FlashMessage';

const OWNER_NAV = [
    { label: 'My listings', route: 'owner.dashboard', match: ['owner.dashboard', 'owner.properties.*'] },
    { label: 'Profile', route: 'owner.profile.edit', match: ['owner.profile.*'] },
];

const ADMIN_NAV = [
    { label: 'Overview', route: 'admin.dashboard', match: ['admin.dashboard'] },
    { label: 'Owners', route: 'admin.owners.index', match: ['admin.owners.*'] },
    { label: 'Listings', route: 'admin.properties.index', match: ['admin.properties.*'] },
    { label: 'Streets', route: 'admin.streets.index', match: ['admin.streets.*'] },
];

/** Shared shell for the owner workspace and the admin area. */
export default function WorkspaceLayout({ children, maxWidth = 'lg' }) {
    const { auth, appName } = usePage().props;
    const nav = auth.user?.is_admin ? ADMIN_NAV : OWNER_NAV;
    const active = nav.findIndex((item) => item.match.some((name) => route().current(name)));

    return (
        <Box sx={{ minHeight: '100vh' }}>
            <AppBar position="sticky" color="inherit" elevation={0} sx={{ borderBottom: 1, borderColor: 'divider' }}>
                <Toolbar sx={{ gap: 1 }}>
                    <Box
                        component={Link}
                        href={route('home')}
                        sx={{ display: 'flex', alignItems: 'center', gap: 1, color: 'primary.main', textDecoration: 'none', flexGrow: 1 }}
                    >
                        <HomeWorkIcon />
                        <Typography variant="h6" fontWeight={700} noWrap>
                            {appName}
                        </Typography>
                    </Box>
                    <Typography variant="body2" color="text.secondary" sx={{ display: { xs: 'none', sm: 'block' } }}>
                        {auth.user?.name}
                    </Typography>
                    <Button onClick={() => router.post(route('logout'))}>Log out</Button>
                </Toolbar>
                <Tabs value={active === -1 ? false : active} variant="scrollable" scrollButtons="auto" sx={{ px: 1 }}>
                    {nav.map((item) => (
                        <Tab key={item.route} label={item.label} component={Link} href={route(item.route)} />
                    ))}
                </Tabs>
            </AppBar>

            <Container maxWidth={maxWidth} sx={{ py: 3 }}>
                {children}
            </Container>

            <FlashMessage />
        </Box>
    );
}
