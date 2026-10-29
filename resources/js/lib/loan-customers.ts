export function customerUrl(id: string): string {
    return `/loans/customers/${id}`;
}

export function humanizeToken(value: string): string {
    const spaced = value
        .replace(/_/g, ' ')
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/^(tier)\s?([123])$/i, 'Tier $2');
    return spaced.toLowerCase().replace(/^\w/, (letter) => letter.toUpperCase());
}
