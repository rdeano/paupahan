const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });

export function formatPrice(amount) {
    return peso.format(amount ?? 0);
}

export function plural(count, word) {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

/** Drop empty values so search URLs stay short. */
export function cleanQuery(params) {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) =>
            Array.isArray(value) ? value.length > 0 : value !== '' && value !== null && value !== undefined,
        ),
    );
}
