const ngn = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/** Negative balances use brackets, the convention in Nigerian statements. */
export function formatNGN(value: number): string {
    if (value < 0) {
        return `(${ngn.format(Math.abs(value))})`;
    }
    return ngn.format(value);
}

const compactNGN = new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    notation: 'compact',
    maximumFractionDigits: 1,
});

/** Short axis and headline figures, such as ₦1.2M. */
export function formatCompactNGN(value: number): string {
    if (value < 0) {
        return `(${compactNGN.format(Math.abs(value))})`;
    }
    return compactNGN.format(value);
}

export function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
}
