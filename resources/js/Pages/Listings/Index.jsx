import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Box, Button, Card, CardContent, Checkbox, Collapse, Container, FormControl, FormControlLabel, FormGroup,
    Grid2 as Grid, InputAdornment, InputLabel, MenuItem, Select, Stack, TextField, Typography, useMediaQuery,
} from '@mui/material';
import { useTheme } from '@mui/material/styles';
import SearchIcon from '@mui/icons-material/Search';
import TuneIcon from '@mui/icons-material/Tune';
import PublicLayout from '../../Layouts/PublicLayout';
import ListingCard from '../../Components/ListingCard';
import Paginator from '../../Components/Paginator';
import BarangayPicker from '../../Components/BarangayPicker';
import { cleanQuery } from '../../utils';

const EMPTY = { q: '', district: '', barangay: '', type: '', min_price: '', max_price: '', bedrooms: '', amenities: [], sort: 'newest' };

function initialFilters(filters) {
    return {
        ...EMPTY,
        ...filters,
        district: filters.district ? Number(filters.district) : '',
        barangay: filters.barangay ? Number(filters.barangay) : '',
        amenities: (filters.amenities ?? []).map(Number),
    };
}

export default function Index({ listings, filters, options }) {
    const theme = useTheme();
    const isDesktop = useMediaQuery(theme.breakpoints.up('md'));
    const [form, setForm] = useState(() => initialFilters(filters));
    const [showFilters, setShowFilters] = useState(false);

    const set = (key) => (event) => setForm((f) => ({ ...f, [key]: event.target.value }));

    const search = (next = form) => {
        router.get(route('home'), cleanQuery(next), { preserveState: true, preserveScroll: true });
    };

    const submit = (event) => {
        event.preventDefault();
        search();
    };

    const reset = () => {
        setForm(EMPTY);
        search(EMPTY);
    };

    const toggleAmenity = (id) =>
        setForm((f) => ({ ...f, amenities: f.amenities.includes(id) ? f.amenities.filter((a) => a !== id) : [...f.amenities, id] }));

    const filterPanel = (
        <Card>
            <CardContent>
                <Stack spacing={2}>
                    <FormControl fullWidth size="small">
                        <InputLabel>District</InputLabel>
                        <Select label="District" value={form.district} onChange={(e) => setForm((f) => ({ ...f, district: e.target.value, barangay: '' }))}>
                            <MenuItem value="">Any district</MenuItem>
                            {options.districts.filter((d) => d.id).map((d) => (
                                <MenuItem key={d.id} value={d.id}>{d.name}</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <BarangayPicker
                        size="small"
                        districts={options.districts}
                        districtId={form.district || null}
                        value={form.barangay || null}
                        onChange={(id) => setForm((f) => ({ ...f, barangay: id ?? '' }))}
                        label="Any barangay"
                    />
                    <FormControl fullWidth size="small">
                        <InputLabel>Property type</InputLabel>
                        <Select label="Property type" value={form.type} onChange={set('type')}>
                            <MenuItem value="">Any type</MenuItem>
                            {options.types.map((t) => (
                                <MenuItem key={t.value} value={t.value}>{t.label}</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <Stack direction="row" spacing={1}>
                        <TextField size="small" label="Min ₱" type="number" value={form.min_price} onChange={set('min_price')} slotProps={{ htmlInput: { min: 0 } }} />
                        <TextField size="small" label="Max ₱" type="number" value={form.max_price} onChange={set('max_price')} slotProps={{ htmlInput: { min: 0 } }} />
                    </Stack>
                    <FormControl fullWidth size="small">
                        <InputLabel>Bedrooms</InputLabel>
                        <Select label="Bedrooms" value={form.bedrooms} onChange={set('bedrooms')}>
                            <MenuItem value="">Any</MenuItem>
                            {[1, 2, 3, 4].map((n) => (
                                <MenuItem key={n} value={n}>{n}+</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <Box>
                        <Typography variant="subtitle2" gutterBottom>Amenities</Typography>
                        <FormGroup>
                            {options.amenities.map((a) => (
                                <FormControlLabel
                                    key={a.id}
                                    control={<Checkbox size="small" checked={form.amenities.includes(a.id)} onChange={() => toggleAmenity(a.id)} />}
                                    label={<Typography variant="body2">{a.name}</Typography>}
                                />
                            ))}
                        </FormGroup>
                    </Box>
                    <Stack direction="row" spacing={1}>
                        <Button type="submit" variant="contained" fullWidth>Apply filters</Button>
                        <Button onClick={reset}>Clear</Button>
                    </Stack>
                </Stack>
            </CardContent>
        </Card>
    );

    return (
        <PublicLayout>
            <Head title="Find a place to rent in Davao City" />

            <Box sx={{ bgcolor: 'primary.main', color: 'primary.contrastText', py: { xs: 4, md: 6 } }}>
                <Container>
                    <Typography variant="h1" gutterBottom>Find a place to rent in Davao City</Typography>
                    <Typography sx={{ opacity: 0.9, mb: 3 }}>Apartments, boarding houses, condos, rooms and bedspaces, posted by owners.</Typography>
                    <Box component="form" onSubmit={submit} sx={{ display: 'flex', gap: 1, bgcolor: 'background.paper', p: 1, borderRadius: 2 }}>
                        <TextField
                            size="small"
                            placeholder="Street, barangay, landmark (e.g. Matina, near Ateneo)"
                            value={form.q}
                            onChange={set('q')}
                            slotProps={{ input: { startAdornment: <InputAdornment position="start"><SearchIcon /></InputAdornment> } }}
                        />
                        <Button type="submit" variant="contained" sx={{ px: 3 }}>Search</Button>
                    </Box>
                </Container>
            </Box>

            <Container sx={{ py: 3 }}>
                <Box component="form" onSubmit={submit}>
                    <Grid container spacing={3}>
                        <Grid size={{ xs: 12, md: 3 }}>
                            {isDesktop ? (
                                filterPanel
                            ) : (
                                <>
                                    <Button startIcon={<TuneIcon />} variant="outlined" fullWidth onClick={() => setShowFilters((s) => !s)}>
                                        {showFilters ? 'Hide filters' : 'Filters'}
                                    </Button>
                                    <Collapse in={showFilters} sx={{ mt: 1 }}>{filterPanel}</Collapse>
                                </>
                            )}
                        </Grid>

                        <Grid size={{ xs: 12, md: 9 }}>
                            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 2 }}>
                                <Typography color="text.secondary">
                                    {listings.total} {listings.total === 1 ? 'place' : 'places'} found
                                </Typography>
                                <FormControl size="small" sx={{ minWidth: 170 }}>
                                    <Select
                                        value={form.sort}
                                        onChange={(e) => {
                                            const next = { ...form, sort: e.target.value };
                                            setForm(next);
                                            search(next);
                                        }}
                                    >
                                        <MenuItem value="newest">Newest first</MenuItem>
                                        <MenuItem value="price_asc">Price: low to high</MenuItem>
                                        <MenuItem value="price_desc">Price: high to low</MenuItem>
                                    </Select>
                                </FormControl>
                            </Stack>

                            {listings.data.length === 0 ? (
                                <Card>
                                    <CardContent sx={{ textAlign: 'center', py: 6 }}>
                                        <Typography variant="h3" gutterBottom>No places match your search</Typography>
                                        <Typography color="text.secondary">Try a wider price range or a different barangay.</Typography>
                                    </CardContent>
                                </Card>
                            ) : (
                                <Grid container spacing={2}>
                                    {listings.data.map((listing) => (
                                        <Grid key={listing.id} size={{ xs: 12, sm: 6, lg: 4 }}>
                                            <ListingCard listing={listing} />
                                        </Grid>
                                    ))}
                                </Grid>
                            )}

                            <Paginator paginator={listings} />
                        </Grid>
                    </Grid>
                </Box>
            </Container>
        </PublicLayout>
    );
}
