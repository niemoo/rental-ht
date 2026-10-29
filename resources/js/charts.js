/**
 * ApexCharts wrappers for the dashboard. Each chart is an Alpine component:
 *
 *   <div x-data="chart('rentalsPerMonth', {{ Js::from($data) }})" wire:ignore></div>
 *
 * Charts re-render when the theme toggles so axis/grid colours follow light/dark mode.
 */

const FONT = "'Poppins', sans-serif";

/** Reads Velzon's CSS variables, so chart colours follow the template and its light/dark switch. */
function palette() {
    const styles = getComputedStyle(document.documentElement);
    const css = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    return {
        mode: dark ? 'dark' : 'light',
        axis: css('--vz-secondary-color', '#878a99'),
        grid: css('--vz-border-color', '#e9ebec'),
        track: css('--vz-tertiary-bg', '#eff2f7'),
        strong: css('--vz-emphasis-color', '#212529'),
        primary: css('--vz-primary', '#405189'),
        primarySoft: css('--vz-info', '#299cdb'),
        success: css('--vz-success', '#0ab39c'),
        warning: css('--vz-warning', '#f7b84b'),
        danger: css('--vz-danger', '#f06548'),
        muted: css('--vz-secondary-color', '#878a99'),
    };
}

const axisLabels = (t) => ({ style: { colors: t.axis, fontFamily: FONT } });

/**
 * Period axis labels for the report charts. A year of months ("Jan 2026" … "Des 2026") doesn't fit side by side,
 * so the axis drops the year when there are many; the tooltip still shows the full label.
 */
const periodAxis = (categories, t) => ({
    categories,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { ...axisLabels(t), rotate: 0, hideOverlappingLabels: true, formatter: (v) => (categories.length > 6 ? String(v ?? '').replace(/ \d{4}$/, '') : v) },
    tickAmount: Math.min(categories.length, 12),
    tooltip: { enabled: false },
});

/** On phones the period labels are turned so a year of months still fits under the chart. */
const periodAxisResponsive = [{ breakpoint: 576, options: { xaxis: { labels: { rotate: -45, rotateAlways: true } } } }];

/** Rp1.250.000 */
const rupiah = (v) => `${v < 0 ? '−' : ''}Rp${Math.abs(Math.round(v)).toLocaleString('id-ID')}`;

/** Short axis labels: 1,2 jt / 250 rb */
const rupiahShort = (v) => {
    const abs = Math.abs(v);
    if (abs >= 1e6) return `${(v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    if (abs >= 1e3) return `${(v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`;
    return `${v}`;
};

const builders = {
    rentalsPerMonth: ({ categories, data }, t) => ({
        chart: { type: 'bar', height: 320, fontFamily: FONT, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: t.mode },
        series: [{ name: 'Penyewaan', data }],
        colors: [t.primary],
        plotOptions: { bar: { borderRadius: 5, columnWidth: '48%' } },
        dataLabels: { enabled: false },
        xaxis: { categories, axisBorder: { show: false }, axisTicks: { show: false }, labels: axisLabels(t) },
        yaxis: { labels: axisLabels(t) },
        grid: { borderColor: t.grid, strokeDashArray: 4, xaxis: { lines: { show: false } } },
        tooltip: { theme: t.mode, y: { formatter: (v) => `${v} penyewaan` } },
    }),

    revenueByMethod: ({ categories, series }, t) => ({
        chart: { type: 'bar', height: 320, stacked: true, fontFamily: FONT, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: t.mode },
        series,
        colors: [t.success, t.primary, t.warning],
        plotOptions: { bar: { borderRadius: 3, columnWidth: categories.length > 20 ? '70%' : '48%' } },
        dataLabels: { enabled: false },
        xaxis: periodAxis(categories, t),
        responsive: periodAxisResponsive,
        yaxis: { labels: { ...axisLabels(t), formatter: rupiahShort } },
        grid: { borderColor: t.grid, strokeDashArray: 4, xaxis: { lines: { show: false } } },
        legend: { show: true, position: 'top', horizontalAlign: 'left', fontFamily: FONT, labels: { colors: t.axis } },
        tooltip: { theme: t.mode, x: { formatter: (v, { dataPointIndex }) => categories[dataPointIndex] ?? v }, y: { formatter: rupiah } },
    }),

    rentalsAndOmzet: ({ categories, rentals, omzet }, t) => ({
        chart: { type: 'line', height: 320, fontFamily: FONT, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: t.mode },
        series: [
            { name: 'Pemesanan', type: 'column', data: rentals },
            { name: 'Omzet', type: 'line', data: omzet },
        ],
        colors: [t.primarySoft, t.primary],
        stroke: { width: [0, 3], curve: 'smooth' },
        plotOptions: { bar: { borderRadius: 3, columnWidth: categories.length > 20 ? '70%' : '48%' } },
        dataLabels: { enabled: false },
        xaxis: periodAxis(categories, t),
        responsive: periodAxisResponsive,
        yaxis: [
            { title: { text: 'Pemesanan', style: { color: t.axis, fontFamily: FONT, fontWeight: 500 } }, labels: { ...axisLabels(t), formatter: (v) => `${Math.round(v)}` }, forceNiceScale: true, min: 0 },
            { opposite: true, title: { text: 'Omzet', style: { color: t.axis, fontFamily: FONT, fontWeight: 500 } }, labels: { ...axisLabels(t), formatter: rupiahShort }, min: 0 },
        ],
        grid: { borderColor: t.grid, strokeDashArray: 4, xaxis: { lines: { show: false } } },
        legend: { show: true, position: 'top', horizontalAlign: 'left', fontFamily: FONT, labels: { colors: t.axis } },
        tooltip: { theme: t.mode, shared: true, intersect: false, x: { formatter: (v, { dataPointIndex }) => categories[dataPointIndex] ?? v }, y: { formatter: (v, { seriesIndex }) => (seriesIndex === 1 ? rupiah(v) : `${v} pemesanan`) } },
    }),

    rentalActivity: ({ categories, out, returned }, t) => ({
        chart: { type: 'area', height: 320, fontFamily: FONT, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: t.mode },
        series: [
            { name: 'Unit keluar', data: out },
            { name: 'Unit kembali', data: returned },
        ],
        colors: [t.primary, t.success],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0, stops: [0, 95] } },
        xaxis: { categories, axisBorder: { show: false }, axisTicks: { show: false }, labels: axisLabels(t) },
        yaxis: { labels: axisLabels(t) },
        grid: { borderColor: t.grid, strokeDashArray: 4 },
        legend: { show: true, position: 'top', horizontalAlign: 'left', fontFamily: FONT, labels: { colors: t.axis } },
        tooltip: { theme: t.mode },
    }),

    utilization: ({ percent }, t) => ({
        chart: { type: 'radialBar', height: 300, fontFamily: FONT, sparkline: { enabled: true }, background: 'transparent' },
        theme: { mode: t.mode },
        series: [percent],
        colors: [t.primary],
        plotOptions: {
            radialBar: {
                startAngle: -110,
                endAngle: 110,
                hollow: { size: '62%' },
                track: { background: t.track, strokeWidth: '100%' },
                dataLabels: {
                    name: { show: true, offsetY: 26, fontSize: '13px', color: t.axis, fontFamily: FONT },
                    value: { show: true, offsetY: -12, fontSize: '36px', fontWeight: 700, color: t.strong, fontFamily: FONT, formatter: (v) => `${v}%` },
                },
            },
        },
        stroke: { lineCap: 'round' },
        labels: ['Sedang dipakai'],
    }),

    fleetStatus: ({ available, onRent, overdue, outOfService = 0 }, t) => ({
        chart: { type: 'donut', height: 300, fontFamily: FONT, background: 'transparent' },
        theme: { mode: t.mode },
        series: [available, onRent, overdue, outOfService],
        labels: ['Tersedia', 'Disewa', 'Terlambat', 'Tidak bisa dipakai'],
        colors: [t.success, t.primary, t.danger, t.muted],
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontFamily: FONT, labels: { colors: t.axis } },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: { show: true, label: 'Unit', fontSize: '13px', color: t.axis, fontFamily: FONT, formatter: () => `${available + onRent + overdue + outOfService}` },
                        value: { fontSize: '26px', fontWeight: 700, color: t.strong, fontFamily: FONT },
                    },
                },
            },
        },
    }),
};

export function registerCharts(Alpine) {
    Alpine.data('chart', (name, data) => ({
        async init() {
            const { default: ApexCharts } = await import('apexcharts');

            // Kept off Alpine's reactive proxy: ApexCharts mutates its instance heavily.
            let instance = null;
            const render = () => {
                instance?.destroy();
                instance = new ApexCharts(this.$el, builders[name](data, palette()));
                instance.render();
            };

            render();
            window.addEventListener('theme-changed', render);

            this.teardown = () => {
                window.removeEventListener('theme-changed', render);
                instance?.destroy();
            };
        },

        destroy() {
            this.teardown?.();
        },
    }));
}
