import { usePage } from "@inertiajs/vue3";

export function currentCurrency() {
    return usePage().props.settings?.default_currency ?? "NGN";
}

/**
 * Format an amount using the currency configured in Settings.
 *
 * decimals defaults to 0 for whole numbers and 2 otherwise; pass an explicit
 * value to pin the display (e.g. invoice tables always show 2 decimals).
 */
export function formatMoney(amount, { currency = null, decimals = null } = {}) {
    const code = currency ?? currentCurrency();
    const value = Number(amount ?? 0);
    const max = decimals ?? (Number.isInteger(value) ? 0 : 2);

    try {
        return new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: code,
            minimumFractionDigits: 0,
            maximumFractionDigits: max,
        }).format(value);
    } catch {
        return `${code} ${value.toLocaleString(undefined, { maximumFractionDigits: max })}`;
    }
}
