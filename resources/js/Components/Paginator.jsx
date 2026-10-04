import { router } from '@inertiajs/react';
import { Box, Pagination } from '@mui/material';

/** MUI pagination for a Laravel paginator, keeping the current query string. */
export default function Paginator({ paginator }) {
    if (!paginator || paginator.last_page <= 1) return null;

    const goTo = (page) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        router.get(url.pathname + url.search, {}, { preserveState: true });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
            <Pagination count={paginator.last_page} page={paginator.current_page} onChange={(_, page) => goTo(page)} color="primary" />
        </Box>
    );
}
