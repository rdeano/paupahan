import { useEffect, useRef } from 'react';
import { Link } from '@inertiajs/react';
import { CircleMarker, MapContainer, Marker, Popup, TileLayer, Tooltip, useMap } from 'react-leaflet';
import { Box, Button, Typography } from '@mui/material';
import SearchIcon from '@mui/icons-material/Search';
import { DAVAO_CENTER, DAVAO_ZOOM, L, TILE_ATTRIBUTION, TILE_URL } from './leaflet';
import { formatPrice } from '../../utils';

const priceIcon = (price) => L.divIcon({
    className: '',
    iconSize: null,
    html: `<div style="transform:translate(-50%,-100%);display:inline-block;white-space:nowrap;background:#0f766e;color:#fff;
        font:600 12px/1 system-ui,sans-serif;padding:5px 8px;border-radius:12px;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)">
        ${formatPrice(price)}</div>`,
});

/**
 * Frame the results: on first load and after every new search (searchKey changes). Goes to the typed
 * place if it was found, or else to the matching listings. When the renter has picked a map area,
 * that area is kept instead.
 */
function FitToResults({ area, place, listings, searchKey }) {
    const map = useMap();
    const firstRun = useRef(true);

    useEffect(() => {
        const move = (bounds, options) => (firstRun.current
            ? map.fitBounds(bounds, options)
            : map.flyToBounds(bounds, { ...options, duration: 0.8 }));

        if (firstRun.current && area) {
            map.fitBounds([[area.south, area.west], [area.north, area.east]]);
        } else if (!area && place) {
            const { south, north, west, east } = place.bounds;
            move([[south, west], [north, east]], { maxZoom: 16 });
        } else if (!area && listings.length > 0) {
            move(listings.map((l) => [l.location.lat, l.location.lng]), { padding: [40, 40], maxZoom: 16 });
        }
        firstRun.current = false;
    }, [searchKey]); // eslint-disable-line react-hooks/exhaustive-deps

    return null;
}

function SearchAreaButton({ onSearchArea }) {
    const map = useMap();
    const box = useRef(null);

    // Keep taps and drags on the button from also moving the map.
    useEffect(() => {
        if (box.current) {
            L.DomEvent.disableClickPropagation(box.current);
            L.DomEvent.disableScrollPropagation(box.current);
        }
    }, []);

    const search = () => {
        const bounds = map.getBounds();
        const round = (n) => Math.round(n * 1e6) / 1e6;
        onSearchArea({
            north: round(bounds.getNorth()),
            south: round(bounds.getSouth()),
            east: round(bounds.getEast()),
            west: round(bounds.getWest()),
        });
    };

    return (
        <Box ref={box} sx={{ position: 'absolute', top: 12, left: '50%', transform: 'translateX(-50%)', zIndex: 1000 }}>
            <Button variant="contained" size="small" startIcon={<SearchIcon />} onClick={search} sx={{ boxShadow: 3 }}>
                Search this area
            </Button>
        </Box>
    );
}

/** Search results as price markers. Positions are approximate, never the exact house. */
export default function ListingsMap({ listings, area, place = null, searchKey, onSearchArea, height = 480 }) {
    return (
        <Box sx={{ height, borderRadius: 2, overflow: 'hidden', border: 1, borderColor: 'divider', position: 'relative' }}>
            <MapContainer center={DAVAO_CENTER} zoom={DAVAO_ZOOM} style={{ height: '100%' }}>
                <TileLayer url={TILE_URL} attribution={TILE_ATTRIBUTION} />
                <FitToResults area={area} place={place} listings={listings} searchKey={searchKey} />
                {place && (
                    <CircleMarker center={[place.lat, place.lng]} radius={9} pathOptions={{ color: '#fff', weight: 3, fillColor: '#d32f2f', fillOpacity: 1 }}>
                        <Tooltip direction="top" offset={[0, -8]}>{place.name}</Tooltip>
                    </CircleMarker>
                )}
                <SearchAreaButton onSearchArea={onSearchArea} />
                {listings.map((listing) => (
                    <Marker key={listing.id} position={[listing.location.lat, listing.location.lng]} icon={priceIcon(listing.price_monthly)}>
                        <Popup>
                            <Box sx={{ width: 200 }}>
                                {listing.cover_url && (
                                    <Box component="img" src={listing.cover_url} alt="" sx={{ width: '100%', aspectRatio: '4 / 3', objectFit: 'cover', borderRadius: 1, display: 'block' }} />
                                )}
                                <Typography variant="subtitle2" color="primary" sx={{ mt: 1 }}>{formatPrice(listing.price_monthly)} / month</Typography>
                                <Typography variant="body2" fontWeight={500}>{listing.title}</Typography>
                                <Typography variant="caption" color="text.secondary" component="p">{listing.address}</Typography>
                                <Button component={Link} href={route('listings.show', listing.slug)} size="small" variant="contained" fullWidth sx={{ mt: 1, '&&': { color: 'common.white' } }}>
                                    View listing
                                </Button>
                            </Box>
                        </Popup>
                    </Marker>
                ))}
            </MapContainer>
        </Box>
    );
}
