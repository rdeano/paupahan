import { createTheme } from '@mui/material/styles';

const theme = createTheme({
    palette: {
        primary: { main: '#0f766e' },
        secondary: { main: '#c2410c' },
        background: { default: '#f7f7f5' },
    },
    shape: { borderRadius: 10 },
    typography: {
        fontFamily: 'Roboto, system-ui, -apple-system, "Segoe UI", sans-serif',
        h1: { fontSize: '2rem', fontWeight: 700 },
        h2: { fontSize: '1.6rem', fontWeight: 700 },
        h3: { fontSize: '1.3rem', fontWeight: 700 },
        button: { textTransform: 'none', fontWeight: 600 },
    },
    components: {
        MuiCard: { defaultProps: { variant: 'outlined' } },
        MuiTextField: { defaultProps: { fullWidth: true } },
        MuiButton: { defaultProps: { disableElevation: true } },
    },
});

export default theme;
