import { useEffect, useState } from 'react';
import { Autocomplete, TextField } from '@mui/material';

/**
 * Street autocomplete for one barangay. Picking a listed street sets `streetId`;
 * typing a street that isn't listed sets `newStreet`, which is saved as pending for admin review.
 */
export default function StreetPicker({ barangayId, streetId, streetName, onChange, error, helperText }) {
    const [streets, setStreets] = useState([]);
    const [loading, setLoading] = useState(false);
    const [input, setInput] = useState(streetName ?? '');

    useEffect(() => {
        if (!barangayId) {
            setStreets([]);
            return;
        }

        let cancelled = false;
        setLoading(true);
        fetch(route('owner.streets.lookup', barangayId), { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => !cancelled && setStreets(data))
            .finally(() => !cancelled && setLoading(false));

        return () => {
            cancelled = true;
        };
    }, [barangayId]);

    const selected = streets.find((s) => s.id === streetId) ?? null;

    const handleInput = (_, value, reason) => {
        setInput(value);
        if (reason !== 'input') return;

        const match = streets.find((s) => s.name.toLowerCase() === value.trim().toLowerCase());
        onChange(match ? { street_id: match.id, new_street: '' } : { street_id: null, new_street: value });
    };

    return (
        <Autocomplete
            freeSolo
            disabled={!barangayId}
            loading={loading}
            options={streets}
            getOptionLabel={(o) => (typeof o === 'string' ? o : o.name)}
            isOptionEqualToValue={(a, b) => a.id === b.id}
            value={selected}
            inputValue={input}
            onInputChange={handleInput}
            onChange={(_, option) => {
                if (option && typeof option === 'object') onChange({ street_id: option.id, new_street: '' });
                else if (!option) onChange({ street_id: null, new_street: '' });
            }}
            renderOption={(props, option) => {
                const { key, ...rest } = props;
                return (
                    <li key={option.id} {...rest}>
                        {option.name}
                        {option.pending ? ' (awaiting review)' : ''}
                    </li>
                );
            }}
            renderInput={(params) => (
                <TextField
                    {...params}
                    label="Street"
                    error={error}
                    helperText={helperText ?? (barangayId ? "Pick from the list, or type it if it's missing." : 'Choose a barangay first.')}
                />
            )}
        />
    );
}
