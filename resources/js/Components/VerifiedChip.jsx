import { Chip, Tooltip } from '@mui/material';
import VerifiedIcon from '@mui/icons-material/Verified';
import SupportAgentIcon from '@mui/icons-material/SupportAgent';

/** Owner trust badge. Owners an admin onboarded show "Posted by Admin" instead of their verification status. */
export default function VerifiedChip({ verified, addedByAdmin = false, size = 'small' }) {
    if (addedByAdmin) {
        return (
            <Tooltip title="An admin posted this listing on the owner's behalf">
                <Chip size={size} color="primary" variant="outlined" icon={<SupportAgentIcon />} label="Posted by Admin" />
            </Tooltip>
        );
    }

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
