import { Chip, Tooltip } from '@mui/material';
import VerifiedIcon from '@mui/icons-material/Verified';

export default function VerifiedChip({ verified, size = 'small' }) {
    return verified ? (
        <Tooltip title="This owner has verified their email address">
            <Chip size={size} color="success" variant="outlined" icon={<VerifiedIcon />} label="Verified" />
        </Tooltip>
    ) : (
        <Tooltip title="This owner has not verified their email address yet">
            <Chip size={size} variant="outlined" label="Unverified" />
        </Tooltip>
    );
}
