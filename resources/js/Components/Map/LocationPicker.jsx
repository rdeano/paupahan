import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet';
import { Box, Button, FormHelperText, Stack, Typography } from '@mui/material';
import { DAVAO_CENTER, DAVAO_ZOOM, TILE_ATTRIBUTION, TILE_URL } from './leaflet';

function DropPin({ onPick }) {
    useMapEvents({ click: (event) => onPick(event.latlng) });
    return null;
}

const round = (n) => Math.round(n * 1e7) / 1e7;

/** Tap the map, or drag the pin, to mark where the property is. */
export default function LocationPicker({ latitude, longitude, onChange, error }) {
    const pinned = latitude !== null && latitude !== '' && longitude !== null && longitude !== '';
    const position = pinned ? [Number(latitude), Number(longitude)] : null;
    const pick = ({ lat, lng }) => onChange({ latitude: round(lat), longitude: round(lng) });

    return (
        <Box>
            <Box sx={{ height: 320, borderRadius: 2, overflow: 'hidden', border: 1, borderColor: error ? 'error.main' : 'divider' }}>
                <MapContainer center={position ?? DAVAO_CENTER} zoom={position ? 17 : DAVAO_ZOOM} style={{ height: '100%' }}>
                    <TileLayer url={TILE_URL} attribution={TILE_ATTRIBUTION} />
                    <DropPin onPick={pick} />
                    {position && (
                        <Marker
                            position={position}
                            draggable
                            eventHandlers={{ dragend: (event) => pick(event.target.getLatLng()) }}
                        />
                    )}
                </MapContainer>
            </Box>
            <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mt: 1 }}>
                <Typography variant="body2" color="text.secondary">
                    {pinned
                        ? 'Drag the pin to adjust. Renters only see the approximate area, never the exact spot.'
                        : 'Tap the map where the property is so renters can find it with map search.'}
                </Typography>
                {pinned && <Button size="small" onClick={() => onChange({ latitude: null, longitude: null })}>Remove pin</Button>}
            </Stack>
            {error && <FormHelperText error>{error}</FormHelperText>}
        </Box>
    );
}
