import { useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Alert, Box, Button, Chip, Grid2 as Grid, IconButton, LinearProgress, Stack, Tooltip, Typography } from '@mui/material';
import AddPhotoAlternateIcon from '@mui/icons-material/AddPhotoAlternate';
import DeleteOutlineIcon from '@mui/icons-material/DeleteOutline';
import StarIcon from '@mui/icons-material/Star';
import StarBorderIcon from '@mui/icons-material/StarBorder';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import ArrowForwardIcon from '@mui/icons-material/ArrowForward';

const options = { preserveScroll: true, preserveState: true };

export default function PhotoManager({ property }) {
    const input = useRef(null);
    const [progress, setProgress] = useState(null);
    const [errors, setErrors] = useState({});
    const photos = property.photos;
    const remaining = property.max_photos - photos.length;

    const upload = (event) => {
        const files = Array.from(event.target.files ?? []);
        if (files.length === 0) return;

        if (files.length > remaining) {
            setErrors({ photos: `You can add ${remaining} more photo${remaining === 1 ? '' : 's'} (maximum ${property.max_photos}).` });
            event.target.value = '';
            return;
        }

        router.post(route('owner.photos.store', property.slug), { photos: files }, {
            ...options,
            forceFormData: true,
            onProgress: (p) => setProgress(p?.percentage ?? null),
            onError: setErrors,
            onSuccess: () => setErrors({}),
            onFinish: () => {
                setProgress(null);
                event.target.value = '';
            },
        });
    };

    const move = (index, delta) => {
        const ids = photos.map((p) => p.id);
        const target = index + delta;
        [ids[index], ids[target]] = [ids[target], ids[index]];
        router.patch(route('owner.photos.reorder', property.slug), { ids }, options);
    };

    const setCover = (photo) => router.patch(route('owner.photos.cover', [property.slug, photo.id]), {}, options);

    const remove = (photo) => {
        if (window.confirm('Delete this photo?')) {
            router.delete(route('owner.photos.destroy', [property.slug, photo.id]), options);
        }
    };

    const errorText = Object.values(errors)[0];

    return (
        <Box>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 2 }}>
                <Typography color="text.secondary" variant="body2">
                    {photos.length} of {property.max_photos} photos. The starred photo is shown first in search results.
                </Typography>
                <Button variant="contained" startIcon={<AddPhotoAlternateIcon />} onClick={() => input.current?.click()} disabled={progress !== null || remaining <= 0}>
                    Add photos
                </Button>
                <input ref={input} type="file" accept="image/jpeg,image/png,image/webp" multiple hidden onChange={upload} />
            </Stack>

            {progress !== null && <LinearProgress variant="determinate" value={progress} sx={{ mb: 2 }} />}
            {errorText && <Alert severity="error" sx={{ mb: 2 }}>{errorText}</Alert>}

            {photos.length === 0 ? (
                <Alert severity="info">Listings with photos get far more inquiries. Add at least one before publishing.</Alert>
            ) : (
                <Grid container spacing={2}>
                    {photos.map((photo, index) => (
                        <Grid key={photo.id} size={{ xs: 6, sm: 4, md: 3 }}>
                            <Box sx={{ position: 'relative', borderRadius: 2, overflow: 'hidden', border: 2, borderColor: photo.is_cover ? 'primary.main' : 'transparent' }}>
                                <Box component="img" src={photo.thumb_url} alt="" sx={{ width: '100%', aspectRatio: '4 / 3', objectFit: 'cover', display: 'block' }} />
                                {photo.is_cover && <Chip size="small" color="primary" label="Cover" sx={{ position: 'absolute', top: 6, left: 6 }} />}
                            </Box>
                            <Stack direction="row" justifyContent="space-between">
                                <Box>
                                    <IconButton size="small" disabled={index === 0} onClick={() => move(index, -1)} aria-label="Move left"><ArrowBackIcon fontSize="small" /></IconButton>
                                    <IconButton size="small" disabled={index === photos.length - 1} onClick={() => move(index, 1)} aria-label="Move right"><ArrowForwardIcon fontSize="small" /></IconButton>
                                </Box>
                                <Box>
                                    <Tooltip title="Set as cover">
                                        <IconButton size="small" onClick={() => setCover(photo)} color={photo.is_cover ? 'primary' : 'default'} aria-label="Set as cover">
                                            {photo.is_cover ? <StarIcon fontSize="small" /> : <StarBorderIcon fontSize="small" />}
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Delete">
                                        <IconButton size="small" onClick={() => remove(photo)} aria-label="Delete photo"><DeleteOutlineIcon fontSize="small" /></IconButton>
                                    </Tooltip>
                                </Box>
                            </Stack>
                        </Grid>
                    ))}
                </Grid>
            )}
        </Box>
    );
}
