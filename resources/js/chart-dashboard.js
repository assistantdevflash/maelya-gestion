/**
 * Graphique du chiffre d'affaires (tableau de bord).
 *
 * Les données arrivent par une balise <script type="application/json"> :
 * pas de code PHP interpolé dans du JavaScript, et le graphique se dessine
 * même hors connexion puisque Chart.js est servi par l'application.
 */
import Chart from './chart.js';

function initialiser() {
    const canvas = document.getElementById('caChart');
    const source = document.getElementById('caChartData');

    if (!canvas || !source) {
        return;
    }

    const chartData = JSON.parse(source.textContent || '{"labels":[],"values":[]}');

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'CA (FCFA)',
                data: chartData.values,
                fill: true,
                backgroundColor: 'rgba(139, 92, 246, 0.08)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 2,
                pointBackgroundColor: 'rgb(139, 92, 246)',
                pointRadius: 3,
                tension: 0.4,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        callback: v => new Intl.NumberFormat('fr-FR').format(v),
                        font: { size: 11 },
                    },
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } },
                },
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiser);
} else {
    initialiser();
}
