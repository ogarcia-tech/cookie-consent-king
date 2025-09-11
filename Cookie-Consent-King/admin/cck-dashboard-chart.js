document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('cck-consent-chart');
    if (ctx && typeof cckChartData !== 'undefined') {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: cckChartData.labels,
                datasets: [{
                    label: 'Consent Actions',
                    data: cckChartData.data,
                    backgroundColor: ['#4CAF50', '#F44336', '#2196F3', '#FFC107'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                }
            }
        });
    }
});