import { Head, Link, router } from '@inertiajs/react';
import { Button, Chip, Link as MuiLink, Paper, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Typography } from '@mui/material';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import AdminSearch from '../../Components/AdminSearch';
import Paginator from '../../Components/Paginator';

export default function Owners({ owners, filters }) {
    const toggle = (owner) => {
        const suspend = owner.status === 'active';
        if (suspend && !window.confirm(`Suspend ${owner.name}? Their listings will be hidden.`)) return;
        router.patch(route('admin.owners.status', owner.id), { suspend }, { preserveScroll: true });
    };

    return (
        <WorkspaceLayout>
            <Head title="Owners" />
            <Typography variant="h2" sx={{ mb: 2 }}>Owners</Typography>
            <AdminSearch routeName="admin.owners.index" value={filters.q} placeholder="Name or email" />

            <TableContainer component={Paper} variant="outlined">
                <Table size="small">
                    <TableHead>
                        <TableRow>
                            <TableCell>Name</TableCell>
                            <TableCell>Email</TableCell>
                            <TableCell align="right">Listings</TableCell>
                            <TableCell>Joined</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell />
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {owners.data.map((owner) => (
                            <TableRow key={owner.id}>
                                <TableCell><MuiLink component={Link} href={route('owners.show', owner.id)}>{owner.name}</MuiLink></TableCell>
                                <TableCell>
                                    {owner.email}
                                    {!owner.verified && <Chip size="small" label="unverified" sx={{ ml: 1 }} />}
                                </TableCell>
                                <TableCell align="right">{owner.properties_count}</TableCell>
                                <TableCell>{owner.joined}</TableCell>
                                <TableCell>
                                    <Chip size="small" label={owner.status} color={owner.status === 'active' ? 'success' : 'error'} />
                                </TableCell>
                                <TableCell align="right">
                                    <Button size="small" color={owner.status === 'active' ? 'error' : 'primary'} onClick={() => toggle(owner)}>
                                        {owner.status === 'active' ? 'Suspend' : 'Reactivate'}
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {owners.data.length === 0 && (
                            <TableRow><TableCell colSpan={6} align="center">No owners found.</TableCell></TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>
            <Paginator paginator={owners} />
        </WorkspaceLayout>
    );
}
