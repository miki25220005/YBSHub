<script>
    // Render Page Visits Chart
    const pageVisitsCtx = document.createElement('canvas');
    document.querySelector('.bg-white.rounded-lg.shadow-lg.mb-8.p-6:nth-child(3)').appendChild(pageVisitsCtx);
    new Chart(pageVisitsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($pageNames); ?>,
            datasets: [{
                label: 'Page Visits',
                data: <?php echo json_encode($visitCounts); ?>,
                backgroundColor: '#36b5eb',
                borderColor: '#1f8fc2',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Visit Count', color: '#000000' } },
                x: { title: { display: true, text: 'Page Name (Period)', color: '#000000' } }
            },
            plugins: { legend: { labels: { color: '#000000' } } }
        }
    });

    // Render Device Usage Chart
    const deviceUsageCtx = document.createElement('canvas');
    document.querySelector('.bg-white.rounded-lg.shadow-lg.p-6:last-child').appendChild(deviceUsageCtx);
    new Chart(deviceUsageCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($deviceTypes); ?>,
            datasets: [{
                data: <?php echo json_encode($deviceUsageCounts); ?>,
                backgroundColor: ['#36b5eb', '#ff6384', '#ffcd56', '#4bc0c0'],
                borderColor: ['#1f8fc2', '#ff4d6a', '#ffaa33', '#33a3a3'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { labels: { color: '#000000' } } }
        }
    });
</script>