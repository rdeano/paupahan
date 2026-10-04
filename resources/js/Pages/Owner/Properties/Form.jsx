import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Alert, Button, Card, CardContent, Checkbox, FormControl, FormControlLabel, FormHelperText, Grid2 as Grid,
    InputAdornment, InputLabel, MenuItem, Select, Stack, TextField, Typography,
} from '@mui/material';
import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import BarangayPicker from '../../../Components/BarangayPicker';
import StreetPicker from '../../../Components/StreetPicker';
import PhotoManager from '../../../Components/PhotoManager';

const BLANK = {
    title: '', type: 'apartment', description: '', price_monthly: '', deposit_months: 1, advance_months: 1,
    min_stay_months: '', bedrooms: 1, bathrooms: 1, floor_area_sqm: '', max_occupants: '',
    barangay_id: null, street_id: null, new_street: '', house_number: '', landmark: '', house_rules: '',
    status: 'draft', available_from: '', amenities: [],
};

function Section({ title, subtitle, children }) {
    return (
        <Card>
            <CardContent>
                <Typography variant="h3" gutterBottom>{title}</Typography>
                {subtitle && <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>{subtitle}</Typography>}
                {children}
            </CardContent>
        </Card>
    );
}

export default function PropertyForm({ property, options, unverifiedLimit }) {
    const { auth } = usePage().props;
    const editing = Boolean(property);
    const form = useForm(
        editing
            ? Object.fromEntries(Object.keys(BLANK).map((key) => [key, property[key] ?? BLANK[key]]))
            : BLANK,
    );

    const field = (name) => ({
        value: form.data[name] ?? '',
        onChange: (e) => form.setData(name, e.target.value),
        error: !!form.errors[name],
        helperText: form.errors[name],
    });

    const toggleAmenity = (id) =>
        form.setData('amenities', form.data.amenities.includes(id) ? form.data.amenities.filter((a) => a !== id) : [...form.data.amenities, id]);

    const submit = (event) => {
        event.preventDefault();
        if (editing) form.put(route('owner.properties.update', property.slug), { preserveScroll: true });
        else form.post(route('owner.properties.store'));
    };

    const select = (name, label, items) => (
        <FormControl fullWidth error={!!form.errors[name]}>
            <InputLabel>{label}</InputLabel>
            <Select label={label} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)}>
                {items.map((item) => <MenuItem key={item.value} value={item.value}>{item.label}</MenuItem>)}
            </Select>
            {form.errors[name] && <FormHelperText>{form.errors[name]}</FormHelperText>}
        </FormControl>
    );

    const monthOptions = [0, 1, 2, 3].map((n) => ({ value: n, label: n === 0 ? 'None' : `${n} month${n > 1 ? 's' : ''}` }));
    const hasErrors = Object.keys(form.errors).length > 0;

    return (
        <WorkspaceLayout maxWidth="md">
            <Head title={editing ? `Edit ${property.title}` : 'Add property'} />

            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 3 }}>
                <Typography variant="h2">{editing ? 'Edit property' : 'Add property'}</Typography>
                {editing && <Button component={Link} href={route('listings.show', property.slug)}>View listing</Button>}
            </Stack>

            <Stack spacing={3}>
                {editing && (
                    <Section title="Photos">
                        <PhotoManager property={property} />
                    </Section>
                )}

                <Stack component="form" spacing={3} onSubmit={submit}>
                    <Section title="Basics">
                        <Grid container spacing={2}>
                            <Grid size={12}>
                                <TextField label="Title" placeholder="e.g. Furnished studio near Ateneo, with Wi-Fi" required {...field('title')} slotProps={{ htmlInput: { maxLength: 120 } }} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>{select('type', 'Property type', options.types)}</Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField label="Monthly rent" type="number" required {...field('price_monthly')}
                                    slotProps={{ input: { startAdornment: <InputAdornment position="start">₱</InputAdornment> }, htmlInput: { min: 1 } }} />
                            </Grid>
                            <Grid size={12}>
                                <TextField label="Description" multiline minRows={5} required {...field('description')}
                                    placeholder="What's included, who it's best for, nearby schools, malls, jeepney routes…" />
                            </Grid>
                        </Grid>
                    </Section>

                    <Section title="Location" subtitle="Renters see the street, barangay and landmark. Your house or unit number stays private.">
                        <Grid container spacing={2}>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <BarangayPicker
                                    districts={options.districts}
                                    value={form.data.barangay_id}
                                    onChange={(id) => form.setData((d) => ({ ...d, barangay_id: id, street_id: null, new_street: '' }))}
                                    required
                                    error={!!form.errors.barangay_id}
                                    helperText={form.errors.barangay_id}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <StreetPicker
                                    key={form.data.barangay_id ?? 'none'}
                                    barangayId={form.data.barangay_id}
                                    streetId={form.data.street_id}
                                    streetName={form.data.barangay_id === property?.barangay_id ? property?.street_name : ''}
                                    onChange={(value) => form.setData((d) => ({ ...d, ...value }))}
                                    error={!!(form.errors.street_id || form.errors.new_street)}
                                    helperText={form.errors.street_id || form.errors.new_street}
                                />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField label="Landmark" placeholder="e.g. near SM Lanang, behind USEP" {...field('landmark')} />
                            </Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField label="House / unit number (private)" {...field('house_number')} />
                            </Grid>
                        </Grid>
                    </Section>

                    <Section title="Details">
                        <Grid container spacing={2}>
                            <Grid size={{ xs: 6, sm: 3 }}><TextField label="Bedrooms" type="number" helperText="0 for studio / room" {...field('bedrooms')} slotProps={{ htmlInput: { min: 0 } }} /></Grid>
                            <Grid size={{ xs: 6, sm: 3 }}><TextField label="Bathrooms" type="number" {...field('bathrooms')} slotProps={{ htmlInput: { min: 0 } }} /></Grid>
                            <Grid size={{ xs: 6, sm: 3 }}><TextField label="Floor area (m²)" type="number" {...field('floor_area_sqm')} slotProps={{ htmlInput: { min: 1 } }} /></Grid>
                            <Grid size={{ xs: 6, sm: 3 }}><TextField label="Max occupants" type="number" {...field('max_occupants')} slotProps={{ htmlInput: { min: 1 } }} /></Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>{select('deposit_months', 'Deposit', monthOptions)}</Grid>
                            <Grid size={{ xs: 12, sm: 4 }}>{select('advance_months', 'Advance', monthOptions)}</Grid>
                            <Grid size={{ xs: 12, sm: 4 }}><TextField label="Minimum stay (months)" type="number" {...field('min_stay_months')} slotProps={{ htmlInput: { min: 1 } }} /></Grid>
                        </Grid>
                    </Section>

                    <Section title="Amenities">
                        <Grid container>
                            {options.amenities.map((a) => (
                                <Grid key={a.id} size={{ xs: 12, sm: 6, md: 4 }}>
                                    <FormControlLabel control={<Checkbox checked={form.data.amenities.includes(a.id)} onChange={() => toggleAmenity(a.id)} />} label={a.name} />
                                </Grid>
                            ))}
                        </Grid>
                    </Section>

                    <Section title="House rules">
                        <TextField multiline minRows={3} placeholder="e.g. Curfew 11 PM, no smoking inside, visitors until 9 PM" {...field('house_rules')} />
                    </Section>

                    <Section
                        title="Availability"
                        subtitle={`Draft listings are only visible to you. Available and Reserved listings appear in search.${auth.user.verified ? '' : ` Unverified accounts can have up to ${unverifiedLimit} public listings.`}`}
                    >
                        <Grid container spacing={2}>
                            <Grid size={{ xs: 12, sm: 6 }}>{select('status', 'Status', options.statuses)}</Grid>
                            <Grid size={{ xs: 12, sm: 6 }}>
                                <TextField label="Available from" type="date" {...field('available_from')} slotProps={{ inputLabel: { shrink: true } }} />
                            </Grid>
                        </Grid>
                    </Section>

                    {hasErrors && <Alert severity="error">Please fix the highlighted fields.</Alert>}

                    <Stack direction="row" spacing={1} justifyContent="flex-end">
                        <Button component={Link} href={route('owner.dashboard')}>Cancel</Button>
                        <Button type="submit" variant="contained" size="large" disabled={form.processing}>
                            {editing ? 'Save changes' : 'Save and add photos'}
                        </Button>
                    </Stack>
                </Stack>
            </Stack>
        </WorkspaceLayout>
    );
}
