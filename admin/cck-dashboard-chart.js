document.addEventListener('DOMContentLoaded', () => {
    const data = window.cckDashboardData || {};

    // Gráfico 1: Tendencias de Consentimiento (Líneas)
    const trendsCtx = document.getElementById('cck-trends-chart');
    if (trendsCtx && data.trends) {
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: data.trends.labels,
                datasets: [
                    {
                        label: 'Accept All',
                        data: data.trends.accept,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Reject All',
                        data: data.trends.reject,
                        borderColor: '#F44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Custom',
                        data: data.trends.custom,
                        borderColor: '#FFC107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Gráfico 2: Aceptación por Categoría (Barras Horizontales)
    const categoriesCtx = document.getElementById('cck-categories-chart');
    if (categoriesCtx && data.categories) {
        new Chart(categoriesCtx, {
            type: 'bar',
            data: {
                labels: data.categories.labels,
                datasets: [{
                    label: 'Acceptance Rate (%)',
                    data: data.categories.percentages,
                    backgroundColor: ['#2196F3', '#4CAF50', '#FF9800'],
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // <-- Esto hace que el gráfico sea horizontal
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { max: 100, ticks: { callback: (value) => value + '%' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
