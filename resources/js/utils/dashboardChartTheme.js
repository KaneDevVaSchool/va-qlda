/**
 * Theme chung cho Chart.js trên Dashboard — tooltip, animation, font, gradient.
 */

export function chartFont(size = 11) {
    return {
        family: "system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
        size,
        weight: '500',
    };
}

export function chartAnimation() {
    return {
        duration: 950,
        easing: 'easeOutQuart',
    };
}

export function tooltipPremium() {
    return {
        backgroundColor: 'rgba(15, 23, 42, 0.94)',
        titleColor: '#f8fafc',
        bodyColor: '#e2e8f0',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 10,
        displayColors: true,
        boxPadding: 6,
        titleFont: { ...chartFont(12), weight: '600' },
        bodyFont: chartFont(11),
    };
}

/**
 * Gradient fill cho line/area (theo chartArea).
 */
export function lineAreaGradient(ctx, chartArea, topRgb, bottomAlpha = 0.02) {
    if (!chartArea) {
        return `rgba(${topRgb}, 0.15)`;
    }
    const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    g.addColorStop(0, `rgba(${topRgb}, 0.28)`);
    g.addColorStop(0.45, `rgba(${topRgb}, 0.08)`);
    g.addColorStop(1, `rgba(${topRgb}, ${bottomAlpha})`);
    return g;
}

/**
 * Gradient dọc cho cột (bar) — mỗi cột một sắc độ.
 */
export function barVerticalGradient(ctx, chartArea, rgb) {
    if (!chartArea) {
        return `rgb(${rgb})`;
    }
    const g = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    g.addColorStop(0, `rgba(${rgb}, 0.38)`);
    g.addColorStop(1, `rgba(${rgb}, 0.72)`);
    return g;
}

export function scalesLightGrid(textColor, gridColor) {
    return {
        x: {
            ticks: { color: textColor, font: chartFont(10) },
            grid: { color: gridColor, drawBorder: false },
            border: { display: false },
        },
        y: {
            ticks: { color: textColor, font: chartFont(10) },
            grid: { color: gridColor, drawBorder: false },
            border: { display: false },
            beginAtZero: true,
        },
    };
}

export function legendBottom(textColor) {
    return {
        position: 'bottom',
        labels: {
            color: textColor,
            padding: 14,
            usePointStyle: true,
            pointStyle: 'circle',
            font: chartFont(11),
        },
    };
}
