import { Head, router } from '@inertiajs/react';
import { Alert, Button, Paper, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Typography } from '@mui/material';
import WorkspaceLayout from '../../Layouts/WorkspaceLayout';

export default function Streets({ streets }) {
    const approve = (street) => router.patch(route('admin.streets.approve', street.id), {}, { preserveScroll: true });

    const reject = (street) => {
        if (window.confirm(`Reject "${street.name}"? Listings using it will lose their street.`)) {
            router.delete(route('admin.streets.destroy', street.id), { preserveScroll: true });
        }
    };

    return (
        <WorkspaceLayout>
            <Head title="Streets" />
            <Typography variant="h2" sx={{ mb: 1 }}>Streets awaiting review</Typography>
            <Typography color="text.secondary" sx={{ mb: 2 }}>
                Owners add these when a street is missing from the list. Approved streets appear for every owner.
            </Typography>

            {streets.length === 0 ? (
                <Alert severity="success">Nothing to review.</Alert>
            ) : (
                <TableContainer component={Paper} variant="outlined">
                    <Table size="small">
                        <TableHead>
                            <TableRow>
                                <TableCell>Street</TableCell>
                                <TableCell>Barangay</TableCell>
                                <TableCell>District</TableCell>
                                <TableCell>Suggested by</TableCell>
                                <TableCell>Date</TableCell>
                                <TableCell />
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {streets.map((street) => (
                                <TableRow key={street.id}>
                                    <TableCell>{street.name}</TableCell>
                                    <TableCell>{street.barangay}</TableCell>
                                    <TableCell>{street.district}</TableCell>
                                    <TableCell>{street.suggested_by}</TableCell>
                                    <TableCell>{street.created_at}</TableCell>
                                    <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                        <Button size="small" variant="contained" onClick={() => approve(street)}>Approve</Button>
                                        <Button size="small" color="error" onClick={() => reject(street)}>Reject</Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            )}
        </WorkspaceLayout>
    );
}
