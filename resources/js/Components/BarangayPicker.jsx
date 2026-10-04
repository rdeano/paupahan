import { useMemo } from 'react';
import { Autocomplete, TextField } from '@mui/material';

/** Barangay autocomplete grouped by district. `value` is a barangay id. */
export default function BarangayPicker({ districts, value, onChange, districtId = null, label = 'Barangay', error, helperText, required, size }) {
    const options = useMemo(
        () =>
            districts
                .filter((d) => !districtId || d.id === districtId)
                .flatMap((d) => d.barangays.map((b) => ({ ...b, district: d.name }))),
        [districts, districtId],
    );

    const selected = options.find((o) => o.id === value) ?? null;

    return (
        <Autocomplete
            options={options}
            groupBy={(o) => o.district}
            getOptionLabel={(o) => o.name}
            isOptionEqualToValue={(a, b) => a.id === b.id}
            value={selected}
            onChange={(_, option) => onChange(option?.id ?? null)}
            size={size}
            renderInput={(params) => <TextField {...params} label={label} required={required} error={error} helperText={helperText} />}
        />
    );
}
