import { Circle, MapContainer, TileLayer } from 'react-leaflet';
import { Box } from '@mui/material';
import { TILE_ATTRIBUTION, TILE_URL } from './leaflet';

/** A shaded circle around the listing's approximate position. The exact pin is never sent to renters. */
export default function ApproximateLocationMap({ location, radius }) {
    return (
        <Box sx={{ height: 280, borderRadius: 2, overflow: 'hidden', border: 1, borderColor: 'divider' }}>
            <MapContainer center={[location.lat, location.lng]} zoom={15} scrollWheelZoom={false} style={{ height: '100%' }}>
                <TileLayer url={TILE_URL} attribution={TILE_ATTRIBUTION} />
                <Circle center={[location.lat, location.lng]} radius={radius} pathOptions={{ color: '#0f766e', fillOpacity: 0.2 }} />
            </MapContainer>
        </Box>
    );
}
