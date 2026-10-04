import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button, Stack, TextField } from '@mui/material';

export default function AdminSearch({ routeName, value, placeholder }) {
    const [q, setQ] = useState(value ?? '');

    const submit = (event) => {
        event.preventDefault();
        router.get(route(routeName), q ? { q } : {}, { preserveState: true });
    };

    return (
        <Stack component="form" direction="row" spacing={1} onSubmit={submit} sx={{ mb: 2, maxWidth: 480 }}>
            <TextField size="small" placeholder={placeholder} value={q} onChange={(e) => setQ(e.target.value)} />
            <Button type="submit" variant="outlined">Search</Button>
        </Stack>
    );
}
