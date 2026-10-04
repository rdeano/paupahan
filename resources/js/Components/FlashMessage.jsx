import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Alert, Snackbar } from '@mui/material';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [message, setMessage] = useState(null);

    useEffect(() => {
        if (flash?.success) setMessage({ severity: 'success', text: flash.success });
        else if (flash?.error) setMessage({ severity: 'error', text: flash.error });
    }, [flash?.success, flash?.error]);

    return (
        <Snackbar
            open={Boolean(message)}
            autoHideDuration={4000}
            onClose={() => setMessage(null)}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
        >
            {message ? (
                <Alert severity={message.severity} variant="filled" onClose={() => setMessage(null)}>
                    {message.text}
                </Alert>
            ) : undefined}
        </Snackbar>
    );
}
