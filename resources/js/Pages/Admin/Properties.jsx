import { Head, Link, router } from '@inertiajs/react';
import { Button, Chip, Link as MuiLink, Paper, Stack, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Typography } from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';
import AdminSearch from '../../Components/AdminSearch';
import Paginator from '../../Components/Paginator';
import { formatPrice } from '../../utils';

export default function Properties({ properties, filters }) {
    const unpublish = (property) => {
        if (window.confirm(`Unpublish "${property.title}"?`)) {
            router.patch(route('admin.properties.unpublish', property.slug), {}, { preserveScroll: true });
        }
    };

    const remove = (property) => {
        if (window.confirm(`Permanently remove "${property.title}" and its photos?`)) {
            router.delete(route('admin.properties.destroy', property.slug), { preserveScroll: true });
        }
    };

    return (
        <WorkspaceLayout>
            <Head title="Listings" />
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 2 }}>
                <Typography variant="h2">Listings</Typography>
                <Button variant="contained" startIcon={<AddIcon />} component={Link} href={route('admin.properties.create')}>Add listing</Button>
            </Stack>
            <AdminSearch routeName="admin.properties.index" value={filters.q} placeholder="Title or owner name" />

            <TableContainer component={Paper} variant="outlined">
                <Table size="small">
                    <TableHead>
                        <TableRow>
                            <TableCell>Title</TableCell>
                            <TableCell>Owner</TableCell>
                            <TableCell>Address</TableCell>
                            <TableCell align="right">Rent</TableCell>
                            <TableCell>Status</TableCell>
                            <TableCell />
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {properties.data.map((property) => (
                            <TableRow key={property.id}>
                                <TableCell><MuiLink component={Link} href={route('listings.show', property.slug)}>{property.title}</MuiLink></TableCell>
                                <TableCell>{property.owner}</TableCell>
                                <TableCell>{property.address}</TableCell>
                                <TableCell align="right">{formatPrice(property.price_monthly)}</TableCell>
                                <TableCell><Chip size="small" label={property.status_label} /></TableCell>
                                <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                    <Button size="small" component={Link} href={route('admin.properties.edit', property.slug)}>Edit</Button>
                                    {property.status !== 'archived' && <Button size="small" onClick={() => unpublish(property)}>Unpublish</Button>}
                                    <Button size="small" color="error" onClick={() => remove(property)}>Remove</Button>
                                </TableCell>
                            </TableRow>
                        ))}
                        {properties.data.length === 0 && (
                            <TableRow><TableCell colSpan={6} align="center">No listings found.</TableCell></TableRow>
                        )}
                    </TableBody>
                </Table>
            </TableContainer>
            <Paginator paginator={properties} />
        </WorkspaceLayout>
    );
}
